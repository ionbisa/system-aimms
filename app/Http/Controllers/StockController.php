<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class StockController extends Controller
{
    protected array $assetManagementTypes = [
        'Delivery Cars',
        'Personal Cars',
        'Motorcycles',
        'Office Assets',
        'Car',
        'Motor',
        'Office',
    ];

    protected function stockColumnsReady(): bool
    {
        foreach (['item_code', 'item_name', 'specification', 'location', 'unit', 'status', 'photo'] as $column) {
            if (! Schema::hasColumn('stocks', $column)) {
                return false;
            }
        }

        return true;
    }

    protected function redirectIfStockSchemaMissing()
    {
        if ($this->stockColumnsReady()) {
            return null;
        }

        return redirect()->route('stock.index')
            ->with('error', 'Struktur tabel stock belum diperbarui. Jalankan migrasi terbaru terlebih dahulu.');
    }

    protected function outboundColumnsReady(): bool
    {
        foreach (['unit', 'description'] as $column) {
            if (! Schema::hasColumn('stock_outbounds', $column)) {
                return false;
            }
        }

        return true;
    }

    protected function redirectIfOutboundSchemaMissing()
    {
        if ($this->outboundColumnsReady()) {
            return null;
        }

        return redirect()->route('stock.outbound')
            ->with('error', 'Struktur tabel histori outbound belum diperbarui. Jalankan migrasi terbaru terlebih dahulu.');
    }

    protected function stockQuery()
    {
        return Stock::query()
            ->where(function ($query) {
                $query->whereNull('asset_id')
                    ->orWhereDoesntHave('asset')
                    ->orWhereHas('asset', function ($assetQuery) {
                        $assetQuery->whereNotIn('type', $this->assetManagementTypes);
                    });
            });
    }

    public function index()
    {
        if (! $this->stockColumnsReady()) {
            return view('stock.index', ['stocks' => collect()]);
        }

        $search = trim((string) request()->query('search'));

        $stocks = $this->stockQuery()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('item_name', 'like', '%' . $search . '%');
            })
            ->orderByDesc('id')
            ->simplePaginate(10)
            ->withQueryString();

        return view('stock.index', compact('stocks', 'search'));
    }

    public function inbound(Request $request)
    {
        if ($redirect = $this->redirectIfStockSchemaMissing()) {
            return $redirect;
        }

        $search = trim((string) $request->query('search'));
        $currentMonth = Carbon::today();
        $monthStart = $currentMonth->copy()->startOfMonth();
        $monthEnd = $currentMonth->copy()->endOfMonth();

        $stocks = $this->stockQuery()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('item_name', 'like', '%' . $search . '%');
            })
            ->orderBy('item_name')
            ->simplePaginate(10)
            ->withQueryString();
        $inbounds = DB::table('stock_inbounds')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('item_name', 'like', '%' . $search . '%');
            })
            ->whereBetween(DB::raw('DATE(created_at)'), [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderByDesc('id')
            ->simplePaginate(10, ['*'], 'inbounds_page')
            ->withQueryString();

        return view('stock.inbound', compact('stocks', 'inbounds', 'search', 'currentMonth'));
    }

    public function storeInbound(Request $request)
    {
        if ($redirect = $this->redirectIfStockSchemaMissing()) {
            return $redirect;
        }

        $validated = $request->validate([
            'stock_id' => 'required|exists:stocks,id',
            'qty' => 'required|integer|min:1',
        ]);

        $stock = Stock::findOrFail($validated['stock_id']);

        DB::table('stock_inbounds')->insert([
            'item_name' => $stock->item_name,
            'qty' => $validated['qty'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stock->increment('qty', $validated['qty']);

        return redirect()->route('stock.inbound')
            ->with('success', 'Barang masuk berhasil dicatat.');
    }

    public function storeItem(Request $request)
    {
        abort_unless($request->user()->hasRole('Master Admin'), 403);

        if ($redirect = $this->redirectIfStockSchemaMissing()) {
            return $redirect;
        }

        $validated = $request->validate([
            'item_code' => 'required|string|max:255|unique:stocks,item_code',
            'item_name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'specification' => 'nullable|string',
            'unit' => 'required|in:PCS,BOX,Roll,DUS,Pack,Botol',
            'status' => 'required|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'qty' => 'required|integer|min:0',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('stocks', 'public');
        }

        Stock::create($validated);

        return redirect()->route('stock.index')
            ->with('success', 'Barang baru berhasil ditambahkan.');
    }

    public function outbound(Request $request)
    {
        if ($redirect = $this->redirectIfStockSchemaMissing()) {
            return $redirect;
        }

        if ($redirect = $this->redirectIfOutboundSchemaMissing()) {
            return $redirect;
        }

        $search = trim((string) $request->query('search'));
        $currentMonth = Carbon::today();
        $monthStart = $currentMonth->copy()->startOfMonth();
        $monthEnd = $currentMonth->copy()->endOfMonth();

        $stocks = $this->stockQuery()
            ->where('qty', '>', 0)
            ->when($search !== '', function ($query) use ($search) {
                $query->where('item_name', 'like', '%' . $search . '%');
            })
            ->orderBy('item_name')
            ->simplePaginate(10)
            ->withQueryString();

        $outbounds = DB::table('stock_outbounds')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('item_name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->whereBetween(DB::raw('DATE(created_at)'), [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderByDesc('id')
            ->simplePaginate(10, ['*'], 'outbounds_page')
            ->withQueryString();

        return view('stock.outbound', compact('stocks', 'outbounds', 'search', 'currentMonth'));
    }

    public function storeOutbound(Request $request)
    {
        return redirect()->route('stock.outbound')
            ->with('error', 'Input manual outbound dinonaktifkan. Barang keluar akan tercatat otomatis dari proses distribusi permintaan barang.');
    }

    public function destroyItem(Request $request, Stock $stock)
    {
        abort_unless($request->user()->hasRole('Master Admin'), 403);

        if ($redirect = $this->redirectIfStockSchemaMissing()) {
            return $redirect;
        }

        if ($stock->photo) {
            Storage::disk('public')->delete($stock->photo);
        }

        $stock->delete();

        return redirect()->route('stock.index')
            ->with('success', 'Barang berhasil dihapus.');
    }

    public function updateItem(Request $request, Stock $stock)
    {
        abort_unless($request->user()->hasRole('Master Admin'), 403);

        if ($redirect = $this->redirectIfStockSchemaMissing()) {
            return $redirect;
        }

        $validated = $request->validate([
            'item_code' => 'required|string|max:255|unique:stocks,item_code,' . $stock->id,
            'item_name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'specification' => 'nullable|string',
            'unit' => 'required|in:PCS,BOX,Roll,DUS,Pack,Botol',
            'status' => 'required|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'qty' => 'required|integer|min:0',
        ]);

        if ($request->hasFile('photo')) {
            if ($stock->photo) {
                Storage::disk('public')->delete($stock->photo);
            }

            $validated['photo'] = $request->file('photo')->store('stocks', 'public');
        }

        $stock->update($validated);

        return redirect()->route('stock.index')
            ->with('success', 'Barang berhasil diupdate.');
    }
}
