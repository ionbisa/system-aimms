<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
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

    protected function stockQuery()
    {
        return Stock::query()
            ->where(function ($query) {
                $query->whereNull('asset_id')
                    ->orWhereDoesntHave('asset')
                    ->orWhereHas('asset', function ($assetQuery) {
                        $assetQuery->whereNotIn('type', $this->assetManagementTypes);
                    });
            })
            ->whereNotNull('item_name');
    }

    protected function approvedPurchaseOrderExpenseQuery()
    {
        return DB::table('purchase_orders')
            ->where(function ($query) {
                if (Schema::hasColumn('purchase_orders', 'overall_status')) {
                    $query->whereIn('overall_status', ['approved', 'done']);

                    return;
                }

                if (Schema::hasColumn('purchase_orders', 'status_label')) {
                    $query->whereIn('status_label', ['Approved', 'Done']);

                    return;
                }

                $query->where('status', 'Approved');
            });
    }

    protected function effectiveExpenseSql(): string
    {
        if (Schema::hasColumn('purchase_orders', 'actual_total_price')) {
            return "COALESCE(actual_total_price, total_price, 0)";
        }

        return "COALESCE(total_price, 0)";
    }

    public function index()
    {
        $currentMonth = Carbon::today();
        $monthStart = $currentMonth->copy()->startOfMonth();
        $monthEnd = $currentMonth->copy()->endOfMonth();

        $stockMasuk = DB::table('stock_inbounds')
            ->whereBetween(DB::raw('DATE(created_at)'), [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->count();
        $stockKeluar = DB::table('stock_outbounds')
            ->whereBetween(DB::raw('DATE(created_at)'), [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->count();
        $stockSaatIni = $this->stockQuery()->sum('qty');
        $totalPengeluaran = $this->approvedPurchaseOrderExpenseQuery()
            ->whereYear(DB::raw('COALESCE(transaction_date, created_at)'), $currentMonth->year)
            ->whereMonth(DB::raw('COALESCE(transaction_date, created_at)'), $currentMonth->month)
            ->sum(DB::raw($this->effectiveExpenseSql()));

        $assetActive = DB::table('assets')->where('status', 'active')->count();
        $assetBroken = DB::table('assets')->where('status', 'maintenance')->count();

        $assetOffice = DB::table('assets')->where('type', 'Office Assets')->count();
        $assetKendaraan = DB::table('assets')
            ->whereIn('type', ['Delivery Cars', 'Personal Cars', 'Motorcycles'])
            ->count();

        $barangMasukHariIni = DB::table('stock_inbounds')
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->get();

        $barangKeluarHariIni = DB::table('stock_outbounds')
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->get();

        $todayPurchase = DB::table('purchase_orders')
            ->select(
                'po_number',
                DB::raw($this->effectiveExpenseSql() . ' as total_price'),
                'created_at',
                DB::raw("
                    CASE
                        WHEN " . (Schema::hasColumn('purchase_orders', 'status_label') ? "status_label IS NOT NULL AND status_label <> '' THEN status_label" : "1 = 0 THEN ''") . "
                        WHEN status = 'Approved' THEN 'Selesai'
                        WHEN status = 'Rejected' THEN 'Pending'
                        ELSE 'Proses'
                    END AS display_status
                ")
            )
            ->whereDate('created_at', today())
            ->get();

        $currentYear = Carbon::today()->year;
        $monthlyExpenseRows = $this->approvedPurchaseOrderExpenseQuery()
            ->selectRaw('MONTH(COALESCE(transaction_date, created_at)) as month_number, SUM(' . $this->effectiveExpenseSql() . ') as monthly_total')
            ->whereYear(DB::raw('COALESCE(transaction_date, created_at)'), $currentYear)
            ->groupByRaw('MONTH(COALESCE(transaction_date, created_at))')
            ->pluck('monthly_total', 'month_number');

        $monthlyExpenseLabels = [];
        $monthlyExpenseValues = [];

        foreach (range(1, 12) as $monthNumber) {
            $monthlyExpenseLabels[] = Carbon::create()->month($monthNumber)->translatedFormat('M');
            $monthlyExpenseValues[] = (int) round((float) ($monthlyExpenseRows[$monthNumber] ?? 0));
        }

        return view('dashboard.index', compact(
            'stockMasuk',
            'stockKeluar',
            'stockSaatIni',
            'totalPengeluaran',
            'currentMonth',
            'assetKendaraan',
            'assetOffice',
            'assetActive',
            'assetBroken',
            'barangMasukHariIni',
            'barangKeluarHariIni',
            'todayPurchase',
            'currentYear',
            'monthlyExpenseLabels',
            'monthlyExpenseValues',
        ));
    }
}
