<?php

namespace App\Http\Controllers;

use App\Models\Asset;
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
        'asset_delivery_cars' => 'Asset Management - Delivery Cars',
        'asset_personal_cars' => 'Asset Management - Personal Cars',
        'asset_motorcycles' => 'Asset Management - Motorcycles',
        'asset_company_assets' => 'Asset Management - Company Assets',
        'company_assets_summary' => 'Ringkasan Company Assets',
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

        if (array_key_exists($reportType, $this->assetReportDefinitions())) {
            [$reportRows, $columns] = $this->assetRows($this->assetReportDefinitions()[$reportType]['types']);
        } else {
            [$reportRows, $columns] = match ($reportType) {
                'stocks' => $this->stockRows($startDate, $endDate),
                'stock_inbounds' => $this->stockInboundRows($startDate, $endDate),
                'stock_outbounds' => $this->stockOutboundRows($startDate, $endDate),
                'company_assets_summary' => $this->companyAssetSummaryRows(),
                default => $this->purchaseOrderRows($startDate, $endDate, $division),
            };
        }

        $filters = [
            'type' => $reportType,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'division' => $reportType === 'purchase_orders' ? $division : '',
            'preset' => $preset,
        ];

        return [$reportRows, $columns, $filters, $this->presets[$preset] . ' - ' . $this->reportTypes[$reportType]];
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
        $columns = ['no', 'kode_barang', 'nama_barang', 'lokasi', 'qty', 'satuan', 'status', 'tanggal_update'];

        if (! $this->hasTable('stocks')) {
            return [collect(), $columns];
        }

        $hasItemCode = $this->hasColumn('stocks', 'item_code');
        $hasItemName = $this->hasColumn('stocks', 'item_name');
        $hasLocation = $this->hasColumn('stocks', 'location');
        $hasUnit = $this->hasColumn('stocks', 'unit');
        $hasStatus = $this->hasColumn('stocks', 'status');
        $hasCreatedAt = $this->hasColumn('stocks', 'created_at');
        $hasUpdatedAt = $this->hasColumn('stocks', 'updated_at');
        $hasAssetId = $this->hasColumn('stocks', 'asset_id');

        $query = DB::table('stocks')
            ->select(
                DB::raw(($hasItemCode ? 'COALESCE(stocks.item_code, "-")' : "'-'") . ' as kode_barang'),
                DB::raw(($hasItemName ? 'COALESCE(stocks.item_name, "-")' : "'-'") . ' as nama_barang'),
                DB::raw(($hasLocation ? 'COALESCE(stocks.location, "-")' : "'-'") . ' as lokasi'),
                DB::raw('COALESCE(stocks.qty, 0) as qty'),
                DB::raw(($hasUnit ? 'COALESCE(stocks.unit, "-")' : "'-'") . ' as satuan'),
                DB::raw(($hasStatus ? 'COALESCE(stocks.status, "-")' : "'-'") . ' as status'),
                DB::raw($hasUpdatedAt
                    ? 'stocks.updated_at as reference_date'
                    : ($hasCreatedAt ? 'stocks.created_at as reference_date' : 'NULL as reference_date'))
            );

        if ($hasAssetId && $this->hasTable('assets')) {
            $query
                ->leftJoin('assets', 'assets.id', '=', 'stocks.asset_id')
                ->where(function ($stockQuery) {
                    $stockQuery->whereNull('stocks.asset_id')
                        ->orWhereNull('assets.id')
                        ->orWhereNotIn('assets.type', $this->assetManagementTypes());
                });
        }

        if ($hasUpdatedAt) {
            $query->orderByDesc('stocks.updated_at');
        } elseif ($hasCreatedAt) {
            $query->orderByDesc('stocks.created_at');
        } elseif ($hasItemName) {
            $query->orderBy('stocks.item_name');
        } else {
            $query->orderByDesc('stocks.id');
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
                    'tanggal_update' => $row->reference_date
                        ? Carbon::parse($row->reference_date)->format('d-m-Y')
                        : '-',
                ];
            });

        return [$rows, $columns];
    }

    protected function stockInboundRows(Carbon $startDate, Carbon $endDate): array
    {
        $columns = ['no', 'nama_barang', 'qty', 'tanggal'];

        if (! $this->hasTable('stock_inbounds')) {
            return [collect(), $columns];
        }

        $hasItemName = $this->hasColumn('stock_inbounds', 'item_name');
        $hasCreatedAt = $this->hasColumn('stock_inbounds', 'created_at');

        $rows = DB::table('stock_inbounds')
            ->select(
                DB::raw(($hasItemName ? 'COALESCE(item_name, "-")' : "'-'") . ' as item_name'),
                DB::raw('COALESCE(qty, 0) as qty'),
                DB::raw($hasCreatedAt ? 'created_at' : 'NULL as created_at')
            )
            ->when($hasCreatedAt, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [
                    $startDate->copy()->startOfDay()->toDateTimeString(),
                    $endDate->copy()->endOfDay()->toDateTimeString(),
                ]);
            })
            ->orderByDesc($hasCreatedAt ? 'created_at' : 'id')
            ->get()
            ->map(function ($row, $index) {
                return [
                    'no' => $index + 1,
                    'nama_barang' => $row->item_name,
                    'qty' => $row->qty,
                    'tanggal' => $row->created_at
                        ? Carbon::parse($row->created_at)->format('d-m-Y')
                        : '-',
                ];
            });

        return [$rows, $columns];
    }

    protected function stockOutboundRows(Carbon $startDate, Carbon $endDate): array
    {
        $columns = ['no', 'nama_barang', 'qty', 'satuan', 'keterangan', 'tanggal'];

        if (! $this->hasTable('stock_outbounds')) {
            return [collect(), $columns];
        }

        $hasUnit = $this->hasColumn('stock_outbounds', 'unit');
        $hasDescription = $this->hasColumn('stock_outbounds', 'description');
        $hasItemName = $this->hasColumn('stock_outbounds', 'item_name');
        $hasCreatedAt = $this->hasColumn('stock_outbounds', 'created_at');

        $rows = DB::table('stock_outbounds')
            ->select(
                DB::raw(($hasItemName ? 'COALESCE(item_name, "-")' : "'-'") . ' as item_name'),
                DB::raw('COALESCE(qty, 0) as qty'),
                DB::raw(($hasUnit ? 'COALESCE(unit, "-")' : "'-'") . ' as satuan'),
                DB::raw(($hasDescription ? 'COALESCE(description, "-")' : "'-'") . ' as keterangan'),
                DB::raw($hasCreatedAt ? 'created_at' : 'NULL as created_at')
            )
            ->when($hasCreatedAt, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [
                    $startDate->copy()->startOfDay()->toDateTimeString(),
                    $endDate->copy()->endOfDay()->toDateTimeString(),
                ]);
            })
            ->orderByDesc($hasCreatedAt ? 'created_at' : 'id')
            ->get()
            ->map(function ($row, $index) {
                return [
                    'no' => $index + 1,
                    'nama_barang' => $row->item_name,
                    'qty' => $row->qty,
                    'satuan' => $row->satuan,
                    'keterangan' => $row->keterangan,
                    'tanggal' => $row->created_at
                        ? Carbon::parse($row->created_at)->format('d-m-Y')
                        : '-',
                ];
            });

        return [$rows, $columns];
    }

    protected function assetRows(array $types): array
    {
        $columns = ['no', 'kode_asset', 'nama_barang', 'tipe', 'lokasi', 'pic', 'status', 'nomor_identitas', 'tanggal_perolehan', 'nilai_asset'];

        if (! $this->hasTable('assets')) {
            return [collect(), $columns];
        }

        $hasPurchaseDate = $this->hasColumn('assets', 'purchase_date');
        $hasValue = $this->hasColumn('assets', 'value');
        $hasPic = $this->hasColumn('assets', 'pic');
        $hasNopol = $this->hasColumn('assets', 'nopol');
        $hasLocation = $this->hasColumn('assets', 'location');
        $hasStatus = $this->hasColumn('assets', 'status');

        $rows = Asset::query()
            ->whereIn('type', $types)
            ->orderBy('name')
            ->orderBy('location')
            ->orderBy('asset_code')
            ->get()
            ->map(function (Asset $asset, int $index) use ($hasLocation, $hasPic, $hasStatus, $hasNopol, $hasPurchaseDate, $hasValue) {
                $purchaseDate = $hasPurchaseDate ? $asset->getAttribute('purchase_date') : null;
                $assetValue = $hasValue ? $asset->getAttribute('value') : null;

                return [
                    'no' => $index + 1,
                    'kode_asset' => $asset->asset_code ?: '-',
                    'nama_barang' => $asset->name ?: '-',
                    'tipe' => $asset->type ?: '-',
                    'lokasi' => $hasLocation ? ($asset->location ?: '-') : '-',
                    'pic' => $hasPic ? ($asset->pic ?: '-') : '-',
                    'status' => $hasStatus ? ($asset->status ?: '-') : '-',
                    'nomor_identitas' => $hasNopol ? ($asset->nopol ?: '-') : '-',
                    'tanggal_perolehan' => $purchaseDate
                        ? Carbon::parse($purchaseDate)->format('d-m-Y')
                        : '-',
                    'nilai_asset' => is_numeric($assetValue)
                        ? 'Rp ' . number_format((float) $assetValue, 0, ',', '.')
                        : '-',
                ];
            });

        return [$rows, $columns];
    }

    protected function companyAssetSummaryRows(): array
    {
        $columns = ['no', 'nama_barang', 'tipe', 'total_unit', 'unit_active', 'unit_maintenance', 'unit_disposed', 'lokasi_penempatan', 'pic_terkait', 'kode_asset_terkait'];

        if (! $this->hasTable('assets')) {
            return [collect(), $columns];
        }

        $assets = Asset::query()
            ->whereIn('type', ['Office Assets', 'Office'])
            ->orderBy('name')
            ->orderBy('location')
            ->get();

        $rows = Asset::buildGroupedSummaries($assets)
            ->values()
            ->map(function (array $summary, int $index) {
                return [
                    'no' => $index + 1,
                    'nama_barang' => $summary['name'],
                    'tipe' => $summary['type'],
                    'total_unit' => $summary['total_qty'],
                    'unit_active' => $summary['active_qty'],
                    'unit_maintenance' => $summary['maintenance_qty'],
                    'unit_disposed' => $summary['disposed_qty'],
                    'lokasi_penempatan' => $summary['locations']->isNotEmpty()
                        ? $summary['locations']->implode(' | ')
                        : '-',
                    'pic_terkait' => $summary['pics']->isNotEmpty()
                        ? $summary['pics']->implode(' | ')
                        : '-',
                    'kode_asset_terkait' => collect($summary['placements'])
                        ->pluck('asset_code')
                        ->implode(' | '),
                ];
            });

        return [$rows, $columns];
    }

    protected function assetReportDefinitions(): array
    {
        return [
            'asset_delivery_cars' => [
                'label' => 'Asset Management - Delivery Cars',
                'types' => ['Delivery Cars', 'Car'],
            ],
            'asset_personal_cars' => [
                'label' => 'Asset Management - Personal Cars',
                'types' => ['Personal Cars'],
            ],
            'asset_motorcycles' => [
                'label' => 'Asset Management - Motorcycles',
                'types' => ['Motorcycles', 'Motorcycle', 'Motor'],
            ],
            'asset_company_assets' => [
                'label' => 'Asset Management - Company Assets',
                'types' => ['Office Assets', 'Office'],
            ],
        ];
    }

    protected function assetManagementTypes(): array
    {
        return collect($this->assetReportDefinitions())
            ->flatMap(fn (array $definition) => $definition['types'])
            ->unique()
            ->values()
            ->all();
    }

    protected function hasTable(string $table): bool
    {
        static $cache = [];

        if (! array_key_exists($table, $cache)) {
            $cache[$table] = DB::getSchemaBuilder()->hasTable($table);
        }

        return $cache[$table];
    }

    protected function hasColumn(string $table, string $column): bool
    {
        static $cache = [];

        $key = $table . '.' . $column;

        if (! array_key_exists($key, $cache)) {
            if (! $this->hasTable($table)) {
                $cache[$key] = false;

                return $cache[$key];
            }

            $cache[$key] = DB::getSchemaBuilder()->hasColumn($table, $column);
        }

        return $cache[$key];
    }
}
