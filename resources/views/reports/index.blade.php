@extends('layouts.app')

@section('content')
<div class="container report-page">
    <style>
        .report-page .report-card {
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
        }

        .report-page .report-card-header {
            border-bottom: 1px solid #eef2f7;
            padding: 1rem 1.25rem 0.9rem;
            background: transparent;
        }

        .report-page .report-card-title {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .report-page .metric-card {
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
        }

        .report-page .metric-label {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }

        .report-page .metric-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.1;
        }

        .report-page .report-table th,
        .report-page .report-table td {
            vertical-align: top;
        }

        .report-page .report-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .report-page .report-list .badge {
            font-weight: 500;
        }

        .report-page .report-code {
            font-family: monospace;
            font-size: 0.82rem;
        }

        .report-page .asset-report-table {
            min-width: 900px;
        }

        .report-page .asset-report-name {
            min-width: 240px;
        }

        .report-page .asset-report-detail {
            min-width: 260px;
        }
    </style>

    @php
        $columnLabels = [
            'no' => 'No',
            'no_po' => 'No PO',
            'tanggal' => 'Tanggal',
            'jenis_transaksi' => 'Jenis Transaksi',
            'divisi' => 'Divisi',
            'kategori' => 'Kategori',
            'uraian' => 'Uraian',
            'vendor' => 'Vendor',
            'qty' => 'Qty',
            'satuan' => 'Satuan',
            'harga_satuan' => 'Harga Satuan',
            'total_harga' => 'Total Harga',
            'keterangan' => 'Keterangan',
            'kode_barang' => 'Kode Barang',
            'kode_asset' => 'Kode Asset',
            'nama_barang' => 'Nama Barang',
            'tipe' => 'Tipe',
            'pic' => 'PIC',
            'nomor_identitas' => 'Nomor / Identitas',
            'tanggal_perolehan' => 'Tanggal Perolehan',
            'nilai_asset' => 'Nilai Asset',
            'total_unit' => 'Total Unit',
            'unit_active' => 'Unit Active',
            'unit_maintenance' => 'Unit Maintenance',
            'unit_disposed' => 'Unit Disposed',
            'lokasi_penempatan' => 'Lokasi Penempatan',
            'pic_terkait' => 'PIC Terkait',
            'kode_asset_terkait' => 'Kode Asset Terkait',
            'lokasi' => 'Lokasi',
            'status' => 'Status',
            'tanggal_update' => 'Tanggal Update',
        ];

        $reportType = $filters['type'] ?? 'purchase_orders';
        $assetReportTypes = [
            'asset_delivery_cars' => 'Delivery Cars',
            'asset_personal_cars' => 'Personal Cars',
            'asset_motorcycles' => 'Motorcycles',
            'asset_company_assets' => 'Company Assets',
        ];
        $statusStyles = [
            'active' => 'success',
            'maintenance' => 'warning',
            'disposed' => 'secondary',
        ];
        $reportRowsCollection = collect($reportRows);
        $isPurchaseOrderReport = $reportType === 'purchase_orders';
        $isStockSnapshotReport = $reportType === 'stocks';
        $isAssetManagementReport = array_key_exists($reportType, $assetReportTypes);
        $isCompanyAssetSummary = $reportType === 'company_assets_summary';
        $isSnapshotReport = $isStockSnapshotReport || $isAssetManagementReport || $isCompanyAssetSummary;
        $isQuantityReport = in_array($reportType, ['stocks', 'stock_inbounds', 'stock_outbounds'], true);

        $companySummaryTotals = [
            'item_groups' => $reportRowsCollection->count(),
            'total_unit' => $reportRowsCollection->sum(fn ($row) => (int) ($row['total_unit'] ?? 0)),
            'unit_active' => $reportRowsCollection->sum(fn ($row) => (int) ($row['unit_active'] ?? 0)),
            'unit_maintenance' => $reportRowsCollection->sum(fn ($row) => (int) ($row['unit_maintenance'] ?? 0)),
            'unit_disposed' => $reportRowsCollection->sum(fn ($row) => (int) ($row['unit_disposed'] ?? 0)),
        ];

        $assetTotals = [
            'total_unit' => $reportRowsCollection->count(),
            'unit_active' => $reportRowsCollection->where('status', 'active')->count(),
            'unit_maintenance' => $reportRowsCollection->where('status', 'maintenance')->count(),
            'unit_disposed' => $reportRowsCollection->where('status', 'disposed')->count(),
        ];

        $quantityTotals = [
            'row_count' => $reportRowsCollection->count(),
            'qty_total' => $reportRowsCollection->sum(fn ($row) => (float) ($row['qty'] ?? 0)),
        ];

        $splitPipeValues = function ($value) {
            return collect(explode('|', (string) $value))
                ->map(fn ($item) => trim($item))
                ->filter(fn ($item) => $item !== '' && $item !== '-')
                ->values();
        };
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">{{ $title }}</h4>
            <small class="text-muted">Pilih jenis data dan rentang tanggal sesuai kebutuhan, lalu unduh Excel atau cetak/simpan sebagai PDF.</small>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.' . $preset) }}" class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label">Jenis Data</label>
            <select name="type" class="form-select">
                @foreach($reportTypes as $reportKey => $reportLabel)
                <option value="{{ $reportKey }}" @selected($reportType === $reportKey)>{{ $reportLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tanggal Awal</label>
            <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Tanggal Akhir</label>
            <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Divisi</label>
            <select name="division" class="form-select">
                <option value="">Semua Divisi</option>
                @foreach($divisionOptions as $divisionOption)
                <option value="{{ $divisionOption }}" @selected(($filters['division'] ?? '') === $divisionOption)>{{ $divisionOption }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-auto align-self-end">
            <button type="submit" class="btn btn-primary">Tampilkan</button>
            <a href="{{ route('reports.' . $preset) }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    @if($isCompanyAssetSummary)
    <div class="alert alert-info border-0 shadow-sm">
        Report ini menampilkan snapshot <strong>Company Assets</strong> saat ini berdasarkan pengelompokan nama barang yang sama. Filter tanggal dan divisi tidak memengaruhi hasil report ini.
    </div>
    @elseif($isAssetManagementReport)
    <div class="alert alert-info border-0 shadow-sm">
        Report ini menampilkan snapshot <strong>{{ $assetReportTypes[$reportType] }}</strong> saat ini. Filter tanggal dan divisi tidak memengaruhi hasil report ini.
    </div>
    @elseif($isStockSnapshotReport)
    <div class="alert alert-secondary border-0 shadow-sm">
        <strong>Stock Barang</strong> menampilkan snapshot stok saat ini. Filter tanggal dipakai untuk judul periode, tetapi isi report mengikuti kondisi stok yang tersedia sekarang.
    </div>
    @endif

    <div class="d-flex gap-2 mb-3">
        <a href="{{ route('reports.export-excel', array_merge(['preset' => $preset], $filters)) }}" class="btn btn-success">
            Download Excel (.csv)
        </a>
        <a href="{{ route('reports.print', array_merge(['preset' => $preset], $filters)) }}" target="_blank" class="btn btn-danger">
            Cetak / Simpan PDF
        </a>
    </div>

    @if($isCompanyAssetSummary)
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-sm-6">
            <div class="metric-card p-3 h-100">
                <div class="metric-label">Jenis Barang</div>
                <div class="metric-value">{{ $companySummaryTotals['item_groups'] }}</div>
                <small class="text-muted">kelompok barang unik</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="metric-card p-3 h-100">
                <div class="metric-label">Total Unit</div>
                <div class="metric-value">{{ $companySummaryTotals['total_unit'] }}</div>
                <small class="text-muted">seluruh unit terdaftar</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="metric-card p-3 h-100">
                <div class="metric-label">Unit Active</div>
                <div class="metric-value text-success">{{ $companySummaryTotals['unit_active'] }}</div>
                <small class="text-muted">siap digunakan</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="metric-card p-3 h-100">
                <div class="metric-label">Maintenance</div>
                <div class="metric-value text-warning">{{ $companySummaryTotals['unit_maintenance'] }}</div>
                <small class="text-muted">sedang perawatan</small>
            </div>
        </div>
    </div>

    <div class="report-card">
        <div class="report-card-header">
            <div class="report-card-title">Rekap Company Assets</div>
            <small class="text-muted">Setiap baris menampilkan gabungan barang dengan nama yang sama, lengkap dengan lokasi penempatan dan PIC terkait.</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 report-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 72px;">No</th>
                        <th>Nama Barang</th>
                        <th>Qty & Status</th>
                        <th>Lokasi Penempatan</th>
                        <th>PIC Terkait</th>
                        <th>Kode Asset Terkait</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportRowsCollection as $row)
                    @php
                        $locations = $splitPipeValues($row['lokasi_penempatan'] ?? '');
                        $pics = $splitPipeValues($row['pic_terkait'] ?? '');
                        $assetCodes = $splitPipeValues($row['kode_asset_terkait'] ?? '');
                    @endphp
                    <tr>
                        <td class="text-center fw-semibold">{{ $row['no'] ?? '-' }}</td>
                        <td>
                            <div class="fw-semibold">{{ $row['nama_barang'] ?? '-' }}</div>
                            <div class="small text-muted">{{ $row['tipe'] ?? '-' }}</div>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge text-bg-primary">{{ $row['total_unit'] ?? 0 }} total</span>
                                <span class="badge text-bg-success">{{ $row['unit_active'] ?? 0 }} active</span>
                                <span class="badge text-bg-warning">{{ $row['unit_maintenance'] ?? 0 }} maintenance</span>
                                @if((int) ($row['unit_disposed'] ?? 0) > 0)
                                <span class="badge text-bg-secondary">{{ $row['unit_disposed'] }} disposed</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($locations->isNotEmpty())
                            <div class="report-list">
                                @foreach($locations as $location)
                                <span class="badge rounded-pill text-bg-light border">{{ $location }}</span>
                                @endforeach
                            </div>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($pics->isNotEmpty())
                            <div class="report-list">
                                @foreach($pics as $pic)
                                <span class="badge rounded-pill text-bg-light border">{{ $pic }}</span>
                                @endforeach
                            </div>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($assetCodes->isNotEmpty())
                            <div class="report-list">
                                @foreach($assetCodes as $assetCode)
                                <span class="badge rounded-pill text-bg-light border report-code">{{ $assetCode }}</span>
                                @endforeach
                            </div>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Tidak ada data pada filter yang dipilih.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @elseif($isAssetManagementReport)
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-sm-6">
            <div class="metric-card p-3 h-100">
                <div class="metric-label">Total Unit</div>
                <div class="metric-value">{{ $assetTotals['total_unit'] }}</div>
                <small class="text-muted">seluruh asset kategori ini</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="metric-card p-3 h-100">
                <div class="metric-label">Unit Active</div>
                <div class="metric-value text-success">{{ $assetTotals['unit_active'] }}</div>
                <small class="text-muted">siap digunakan</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="metric-card p-3 h-100">
                <div class="metric-label">Maintenance</div>
                <div class="metric-value text-warning">{{ $assetTotals['unit_maintenance'] }}</div>
                <small class="text-muted">sedang perawatan</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="metric-card p-3 h-100">
                <div class="metric-label">Disposed</div>
                <div class="metric-value text-secondary">{{ $assetTotals['unit_disposed'] }}</div>
                <small class="text-muted">tidak aktif digunakan</small>
            </div>
        </div>
    </div>

    <div class="report-card">
        <div class="report-card-header">
            <div class="report-card-title">Daftar {{ $assetReportTypes[$reportType] }}</div>
            <small class="text-muted">Setiap baris menampilkan unit asset lengkap dengan lokasi, PIC, status, dan informasi identitas asset.</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 report-table asset-report-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 72px;">No</th>
                        <th class="asset-report-name">Asset</th>
                        <th>Penempatan</th>
                        <th style="width: 150px;">Status</th>
                        <th class="asset-report-detail">Detail Asset</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportRowsCollection as $row)
                    <tr>
                        <td class="text-center fw-semibold">{{ $row['no'] ?? '-' }}</td>
                        <td>
                            <div class="fw-semibold">{{ $row['nama_barang'] ?? '-' }}</div>
                            <div class="small text-muted report-code">{{ $row['kode_asset'] ?? '-' }}</div>
                            <div class="small text-muted">{{ $row['tipe'] ?? '-' }}</div>
                        </td>
                        <td>
                            <div>{{ $row['lokasi'] ?? '-' }}</div>
                            <div class="small text-muted">PIC: {{ $row['pic'] ?? '-' }}</div>
                        </td>
                        <td>
                            @php
                                $statusKey = $row['status'] ?? '';
                            @endphp
                            <span class="badge text-bg-{{ $statusStyles[$statusKey] ?? 'light' }} text-capitalize">
                                {{ $row['status'] ?? '-' }}
                            </span>
                        </td>
                        <td>
                            <div><span class="text-muted small">Nomor:</span> {{ $row['nomor_identitas'] ?? '-' }}</div>
                            <div><span class="text-muted small">Perolehan:</span> {{ $row['tanggal_perolehan'] ?? '-' }}</div>
                            <div><span class="text-muted small">Nilai:</span> {{ $row['nilai_asset'] ?? '-' }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Tidak ada data pada kategori asset ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    @if($isQuantityReport)
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-sm-6">
            <div class="metric-card p-3 h-100">
                <div class="metric-label">Jumlah Baris</div>
                <div class="metric-value">{{ $quantityTotals['row_count'] }}</div>
                <small class="text-muted">data yang tampil di report</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="metric-card p-3 h-100">
                <div class="metric-label">Total Qty</div>
                <div class="metric-value">{{ rtrim(rtrim(number_format($quantityTotals['qty_total'], 2, '.', ''), '0'), '.') }}</div>
                <small class="text-muted">akumulasi qty pada periode terpilih</small>
            </div>
        </div>
    </div>
    @endif

    <div class="report-card">
        <div class="report-card-header">
            <div class="report-card-title">Data Report</div>
            <small class="text-muted">Hasil report ditampilkan dalam format tabel agar mudah dibaca, dicetak, dan diunduh.</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 report-table">
                <thead class="table-light">
                    <tr class="text-center">
                        @foreach($columns as $column)
                        <th>{{ $columnLabels[$column] ?? ucfirst(str_replace('_', ' ', $column)) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportRowsCollection as $row)
                    <tr>
                        @foreach($columns as $column)
                        <td>{{ $row[$column] ?? '-' }}</td>
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="text-center text-muted py-4">Tidak ada data pada filter yang dipilih.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($isPurchaseOrderReport && $reportRowsCollection->isNotEmpty())
                <tfoot>
                    <tr class="table-light">
                        <th colspan="{{ max(count($columns) - 1, 1) }}" class="text-end">Total Nilai Biaya</th>
                        <th>Rp {{ number_format($reportRowsCollection->reduce(function ($carry, $row) {
                            return $carry + (float) preg_replace('/[^0-9]/', '', (string) ($row['total_harga'] ?? '0'));
                        }, 0), 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
