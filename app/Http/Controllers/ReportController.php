<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    protected array $reportTypes = [
        'purchase_orders' => 'Data Pembelian / Purchase Order',
        'stocks' => 'Stock Barang',
        'stock_inbounds' => 'Barang Masuk',
        'stock_outbounds' => 'Barang Keluar',
    ];

    protected array $presets = [
        'daily' => 'Daily Report',
        'weekly' => 'Weekly Report',
        'monthly' => 'Monthly Report',
        'yearly' => 'Year Report',
    ];

    public function daily(Request $request)
    {
        return $this->renderReport($request, 'daily');
    }

    public function weekly(Request $request)
    {
        return $this->renderReport($request, 'weekly');
    }

    public function monthly(Request $request)
    {
        return $this->renderReport($request, 'monthly');
    }

    public function yearly(Request $request)
    {
        return $this->renderReport($request, 'yearly');
    }

    public function exportExcel(Request $request, string $preset): StreamedResponse
    {
        [$reportRows, $columns, $filters, $title] = $this->buildReportPayload($request, $preset);
        $filename = str($title)->slug('-') . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($columns, $reportRows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($reportRows as $row) {
                fputcsv($handle, array_map(fn ($column) => $row[$column] ?? '', $columns));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Request $request, string $preset)
    {
        [$reportRows, $columns, $filters, $title] = $this->buildReportPayload($request, $preset);

        return view('reports.print', compact('reportRows', 'columns', 'filters', 'title'));
    }

    protected function renderReport(Request $request, string $preset)
    {
        [$reportRows, $columns, $filters, $title] = $this->buildReportPayload($request, $preset);

        return view('reports.index', [
            'reportRows' => $reportRows,
            'columns' => $columns,
            'filters' => $filters,
            'title' => $title,
            'reportTypes' => $this->reportTypes,
            'divisionOptions' => $this->purchaseOrderDivisionOptions(),
            'preset' => $preset,
        ]);
    }

    protected function buildReportPayload(Request $request, string $preset): array
    {
        abort_unless(array_key_exists($preset, $this->presets), 404);

        $today = Carbon::today();
        [$defaultStart, $defaultEnd] = $this->defaultDateRange($preset, $today);

        $reportType = (string) $request->query('type', 'purchase_orders');
        if (! array_key_exists($reportType, $this->reportTypes)) {
            $reportType = 'purchase_orders';
        }

        $startDate = $this->parseDate($request->query('start_date'), $defaultStart);
        $endDate = $this->parseDate($request->query('end_date'), $defaultEnd);
        $division = trim((string) $request->query('division'));

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        [$reportRows, $columns] = match ($reportType) {
            'stocks' => $this->stockRows($startDate, $endDate),
            'stock_inbounds' => $this->stockInboundRows($startDate, $endDate),
            'stock_outbounds' => $this->stockOutboundRows($startDate, $endDate),
            default => $this->purchaseOrderRows($startDate, $endDate, $division),
        };

        $filters = [
            'type' => $reportType,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'division' => $reportType === 'purchase_orders' ? $division : '',
            'preset' => $preset,
        ];

        return [$reportRows, $columns, $filters, $this->presets[$preset]];
    }

    protected function defaultDateRange(string $preset, Carbon $today): array
    {
        return match ($preset) {
            'daily' => [$today->copy(), $today->copy()],
            'weekly' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
            'monthly' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'yearly' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
        };
    }

    protected function parseDate(mixed $value, Carbon $fallback): Carbon
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return Carbon::createFromFormat('Y-m-d', $value);
        }

        return $fallback->copy();
    }

    protected function purchaseOrderRows(Carbon $startDate, Carbon $endDate, string $division = ''): array
    {
        $hasTransactionDate = $this->hasColumn('purchase_orders', 'transaction_date');
        $hasTransactionType = $this->hasColumn('purchase_orders', 'transaction_type');
        $hasDivision = $this->hasColumn('purchase_orders', 'division');
        $hasCategory = $this->hasColumn('purchase_orders', 'category');
        $hasDescription = $this->hasColumn('purchase_orders', 'description');
        $hasVendor = $this->hasColumn('purchase_orders', 'vendor');
        $hasQty = $this->hasColumn('purchase_orders', 'qty');
        $hasUnit = $this->hasColumn('purchase_orders', 'unit');
        $hasUnitPrice = $this->hasColumn('purchase_orders', 'unit_price');
        $hasStatusLabel = $this->hasColumn('purchase_orders', 'status_label');
        $effectiveTotalPriceSql = $this->hasColumn('purchase_orders', 'actual_total_price')
            ? 'COALESCE(actual_total_price, total_price, 0)'
            : 'COALESCE(total_price, 0)';

        $rows = DB::table('purchase_orders')
            ->select(
                'po_number',
                DB::raw(($hasTransactionDate ? 'COALESCE(transaction_date, DATE(created_at))' : 'DATE(created_at)') . ' as tanggal'),
                DB::raw(($hasTransactionType ? 'COALESCE(transaction_type, "-")' : "'-'") . ' as jenis_transaksi'),
                DB::raw(($hasDivision ? 'COALESCE(division, "-")' : "'-'") . ' as divisi'),
                DB::raw(($hasCategory ? 'COALESCE(category, "-")' : "'-'") . ' as kategori'),
                DB::raw(($hasDescription ? 'COALESCE(description, "-")' : "'-'") . ' as uraian'),
                DB::raw(($hasVendor ? 'COALESCE(vendor, "-")' : "'-'") . ' as vendor'),
                DB::raw(($hasQty ? 'COALESCE(qty, 0)' : '0') . ' as qty'),
                DB::raw(($hasUnit ? 'COALESCE(unit, "-")' : "'-'") . ' as satuan'),
                DB::raw(($hasUnitPrice ? 'COALESCE(unit_price, 0)' : '0') . ' as harga_satuan'),
                DB::raw($effectiveTotalPriceSql . ' as total_harga'),
                DB::raw("
                    CASE
                        WHEN " . ($hasStatusLabel ? "status_label IS NOT NULL AND status_label <> '' THEN status_label" : "1 = 0 THEN ''") . "
                        WHEN status = 'Approved' THEN 'Selesai'
                        WHEN status = 'Rejected' THEN 'Pending'
                        ELSE 'Proses'
                    END as keterangan
                ")
            )
            ->whereBetween(DB::raw($hasTransactionDate ? 'COALESCE(transaction_date, DATE(created_at))' : 'DATE(created_at)'), [$startDate->toDateString(), $endDate->toDateString()])
            ->when($division !== '' && $hasDivision, fn ($query) => $query->where('division', $division))
            ->orderByDesc(DB::raw($hasTransactionDate ? 'COALESCE(transaction_date, created_at)' : 'created_at'))
            ->get()
            ->map(function ($row, $index) {
                return [
                    'no' => $index + 1,
                    'no_po' => $row->po_number,
                    'tanggal' => Carbon::parse($row->tanggal)->format('d-m-Y'),
                    'jenis_transaksi' => $row->jenis_transaksi,
                    'divisi' => $row->divisi,
                    'kategori' => $row->kategori,
                    'uraian' => $row->uraian,
                    'vendor' => $row->vendor,
                    'qty' => rtrim(rtrim(number_format((float) $row->qty, 2, '.', ''), '0'), '.'),
                    'satuan' => $row->satuan,
                    'harga_satuan' => 'Rp ' . number_format((float) $row->harga_satuan, 0, ',', '.'),
                    'total_harga' => 'Rp ' . number_format((float) $row->total_harga, 0, ',', '.'),
                    'keterangan' => $row->keterangan,
                ];
            });

        return [$rows, ['no', 'no_po', 'tanggal', 'jenis_transaksi', 'divisi', 'kategori', 'uraian', 'vendor', 'qty', 'satuan', 'harga_satuan', 'total_harga', 'keterangan']];
    }

    protected function purchaseOrderDivisionOptions(): array
    {
        if (! $this->hasColumn('purchase_orders', 'division')) {
            return [];
        }

        return DB::table('purchase_orders')
            ->whereNotNull('division')
            ->where('division', '<>', '')
            ->distinct()
            ->orderBy('division')
            ->pluck('division')
            ->all();
    }

    protected function stockRows(Carbon $startDate, Carbon $endDate): array
    {
        $hasItemCode = $this->hasColumn('stocks', 'item_code');
        $hasLocation = $this->hasColumn('stocks', 'location');
        $hasUnit = $this->hasColumn('stocks', 'unit');
        $hasStatus = $this->hasColumn('stocks', 'status');
        $hasCreatedAt = $this->hasColumn('stocks', 'created_at');
        $hasUpdatedAt = $this->hasColumn('stocks', 'updated_at');

        $query = DB::table('stocks')
            ->select(
                DB::raw(($hasItemCode ? 'COALESCE(item_code, "-")' : "'-'") . ' as kode_barang'),
                DB::raw('COALESCE(item_name, "-") as nama_barang'),
                DB::raw(($hasLocation ? 'COALESCE(location, "-")' : "'-'") . ' as lokasi'),
                'qty',
                DB::raw(($hasUnit ? 'COALESCE(unit, "-")' : "'-'") . ' as satuan'),
                DB::raw(($hasStatus ? 'COALESCE(status, "-")' : "'-'") . ' as status'),
                DB::raw($hasUpdatedAt ? 'updated_at' : 'NULL as updated_at')
            );

        if ($hasCreatedAt) {
            $query->whereDate('created_at', '<=', $endDate->toDateString());
        }

        if ($hasItemCode) {
            $query->where('item_code', 'like', 'BRG-%');
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($hasUpdatedAt) {
            $query->orderByDesc('updated_at');
        } else {
            $query->orderBy('item_name');
        }

        $rows = $query
            ->get()
            ->map(function ($row, $index) {
                return [
                    'no' => $index + 1,
                    'kode_barang' => $row->kode_barang,
                    'nama_barang' => $row->nama_barang,
                    'lokasi' => $row->lokasi,
                    'qty' => $row->qty,
                    'satuan' => $row->satuan,
                    'status' => $row->status,
                    'tanggal_update' => $row->updated_at
                        ? Carbon::parse($row->updated_at)->format('d-m-Y')
                        : '-',
                ];
            });

        return [$rows, ['no', 'kode_barang', 'nama_barang', 'lokasi', 'qty', 'satuan', 'status', 'tanggal_update']];
    }

    protected function stockInboundRows(Carbon $startDate, Carbon $endDate): array
    {
        $rows = DB::table('stock_inbounds')
            ->select('item_name', 'qty', 'created_at')
            ->when($this->hasColumn('stocks', 'item_code'), function ($query) {
                $query->whereIn('item_name', function ($subQuery) {
                    $subQuery->from('stocks')
                        ->select('item_name')
                        ->where('item_code', 'like', 'BRG-%');
                });
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($row, $index) {
                return [
                    'no' => $index + 1,
                    'nama_barang' => $row->item_name,
                    'qty' => $row->qty,
                    'tanggal' => Carbon::parse($row->created_at)->format('d-m-Y'),
                ];
            });

        return [$rows, ['no', 'nama_barang', 'qty', 'tanggal']];
    }

    protected function stockOutboundRows(Carbon $startDate, Carbon $endDate): array
    {
        $hasUnit = $this->hasColumn('stock_outbounds', 'unit');
        $hasDescription = $this->hasColumn('stock_outbounds', 'description');

        $rows = DB::table('stock_outbounds')
            ->select(
                'item_name',
                'qty',
                DB::raw(($hasUnit ? 'COALESCE(unit, "-")' : "'-'") . ' as satuan'),
                DB::raw(($hasDescription ? 'COALESCE(description, "-")' : "'-'") . ' as keterangan'),
                'created_at'
            )
            ->when($this->hasColumn('stocks', 'item_code'), function ($query) {
                $query->whereIn('item_name', function ($subQuery) {
                    $subQuery->from('stocks')
                        ->select('item_name')
                        ->where('item_code', 'like', 'BRG-%');
                });
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($row, $index) {
                return [
                    'no' => $index + 1,
                    'nama_barang' => $row->item_name,
                    'qty' => $row->qty,
                    'satuan' => $row->satuan,
                    'keterangan' => $row->keterangan,
                    'tanggal' => Carbon::parse($row->created_at)->format('d-m-Y'),
                ];
            });

        return [$rows, ['no', 'nama_barang', 'qty', 'satuan', 'keterangan', 'tanggal']];
    }

    protected function hasColumn(string $table, string $column): bool
    {
        static $cache = [];

        $key = $table . '.' . $column;

        if (! array_key_exists($key, $cache)) {
            $cache[$key] = DB::getSchemaBuilder()->hasColumn($table, $column);
        }

        return $cache[$key];
    }
}
