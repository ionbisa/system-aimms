<!DOCTYPE html>
<html lang="id">
@php
    /** @var \App\Models\User|null $authUser */
    $authUser = \Illuminate\Support\Facades\Auth::user();
    $printedBy = $authUser?->getAttribute('name') ?? 'User';
@endphp
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #222; }
        h1, h2, h3, p { margin: 0; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #cfcfcf; padding: 8px; vertical-align: top; }
        th { background: #f3f3f3; text-align: left; }
        .page-watermark {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 72px;
            font-weight: 700;
            letter-spacing: 8px;
            color: rgba(13, 110, 253, 0.07);
            transform: rotate(-28deg);
            pointer-events: none;
            z-index: 0;
        }
        .page-frame {
            position: relative;
            z-index: 1;
        }
        .header { border-bottom: 2px solid #222; padding-bottom: 14px; margin-bottom: 18px; }
        .header-table, .signature-table { width: 100%; border: 0; border-collapse: collapse; }
        .header-table td, .signature-table td { border: 0; padding: 0; vertical-align: top; }
        .logo-cell { width: 90px; }
        .logo { width: 72px; height: 72px; object-fit: contain; }
        .company-name { font-size: 22px; font-weight: 700; letter-spacing: 0.5px; }
        .company-subtitle { font-size: 14px; font-weight: 700; margin-top: 2px; }
        .company-address { font-size: 12px; color: #555; margin-top: 6px; line-height: 1.5; }
        .report-title { text-align: center; margin-bottom: 14px; }
        .report-title h2 { font-size: 18px; margin-bottom: 4px; text-transform: uppercase; }
        .report-title p { font-size: 12px; color: #555; }
        .meta { margin-bottom: 16px; font-size: 12px; line-height: 1.7; }
        .actions { margin-bottom: 16px; }
        .actions button { padding: 8px 12px; }
        .signature { margin-top: 32px; font-size: 12px; }
        .signature-box { width: 240px; text-align: center; }
        .signature-space { height: 64px; }
        .footer-accent { height: 6px; background: #0d6efd; margin-top: 24px; border-radius: 999px; }
        .company-asset-summary-table,
        .asset-management-table {
            table-layout: fixed;
            font-size: 10px;
        }
        .company-asset-summary-table td,
        .asset-management-table td {
            word-break: break-word;
            white-space: normal;
        }
        .company-asset-summary-table .stacked-list span,
        .asset-management-table .stacked-list span {
            display: block;
            margin-bottom: 3px;
        }
        .company-asset-summary-table .stacked-list span:last-child,
        .asset-management-table .stacked-list span:last-child {
            margin-bottom: 0;
        }
        .company-asset-summary-table th:nth-child(1),
        .company-asset-summary-table td:nth-child(1) { width: 4%; text-align: center; }
        .company-asset-summary-table th:nth-child(2),
        .company-asset-summary-table td:nth-child(2) { width: 18%; }
        .company-asset-summary-table th:nth-child(3),
        .company-asset-summary-table td:nth-child(3) { width: 12%; }
        .company-asset-summary-table th:nth-child(4),
        .company-asset-summary-table td:nth-child(4) { width: 12%; }
        .company-asset-summary-table th:nth-child(5),
        .company-asset-summary-table td:nth-child(5) { width: 12%; }
        .company-asset-summary-table th:nth-child(6),
        .company-asset-summary-table td:nth-child(6) { width: 12%; }
        .company-asset-summary-table th:nth-child(7),
        .company-asset-summary-table td:nth-child(7) { width: 14%; }
        .company-asset-summary-table th:nth-child(8),
        .company-asset-summary-table td:nth-child(8) { width: 16%; }
        .asset-management-table th:nth-child(1),
        .asset-management-table td:nth-child(1) { width: 5%; text-align: center; }
        .asset-management-table th:nth-child(2),
        .asset-management-table td:nth-child(2) { width: 26%; }
        .asset-management-table th:nth-child(3),
        .asset-management-table td:nth-child(3) { width: 20%; }
        .asset-management-table th:nth-child(4),
        .asset-management-table td:nth-child(4) { width: 12%; }
        .asset-management-table th:nth-child(5),
        .asset-management-table td:nth-child(5) { width: 37%; }
        @media print {
            .actions { display: none; }
            body { margin: 12px; }
        }
    </style>
</head>
<body>
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
        $reportTypeLabels = [
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
        $assetReportTypes = [
            'asset_delivery_cars' => 'Delivery Cars',
            'asset_personal_cars' => 'Personal Cars',
            'asset_motorcycles' => 'Motorcycles',
            'asset_company_assets' => 'Company Assets',
        ];
        $documentNumber = 'DOC/' . strtoupper(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 ]/', '', $title))) . '/' . now()->format('Ymd') . '/' . now()->format('His');
        $reportRowsCollection = collect($reportRows);
        $totalPurchaseOrderAmount = $reportType === 'purchase_orders'
            ? $reportRowsCollection->reduce(function ($carry, $row) {
                $numericValue = preg_replace('/[^0-9]/', '', (string) ($row['total_harga'] ?? '0'));

                return $carry + (float) $numericValue;
            }, 0)
            : 0;
        $isPurchaseOrderReport = $reportType === 'purchase_orders';
        $isStockSnapshotReport = $reportType === 'stocks';
        $isAssetManagementReport = array_key_exists($reportType, $assetReportTypes);
        $isCompanyAssetSummary = $reportType === 'company_assets_summary';
        $isSnapshotReport = $isStockSnapshotReport || $isAssetManagementReport || $isCompanyAssetSummary;
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
        $splitPipeValues = function ($value) {
            return collect(explode('|', (string) $value))
                ->map(fn ($item) => trim($item))
                ->filter(fn ($item) => $item !== '' && $item !== '-')
                ->values();
        };
    @endphp

    <div class="actions">
        <button onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <div class="page-watermark">BANGGA GROUP</div>

    <div class="page-frame">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        <img src="{{ asset('assets/img/logo.png') }}" alt="Bangga Group" class="logo">
                    </td>
                    <td>
                        <div class="company-name">BANGGA GROUP</div>
                        <div class="company-subtitle">Asset Inventory Maintenance and Management System</div>
                        <div class="company-address">
                            Blok Capar 3 RT. 017 RW. 009 Desa Sidangwangi Kec. Sumber Kab. Cirebon 45611<br>
                            Telp. 02318858881 HP. 081221703904 Email : Office@banggagroup.com
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="report-title">
            <h2>{{ $title }}</h2>
            <p>Laporan sesuai filter yang dipilih pengguna</p>
        </div>

        <div class="meta">
            <strong>Nomor Dokumen:</strong> {{ $documentNumber }}<br>
            <strong>Jenis Data:</strong> {{ $reportTypeLabels[$reportType] ?? str_replace('_', ' ', $reportType) }}<br>
            <strong>Periode:</strong> {{ $isSnapshotReport ? 'Snapshot data saat ini' : \Carbon\Carbon::parse($filters['start_date'])->format('d-m-Y') . ' s/d ' . \Carbon\Carbon::parse($filters['end_date'])->format('d-m-Y') }}<br>
            <strong>Divisi:</strong> {{ $isPurchaseOrderReport ? (($filters['division'] ?? '') !== '' ? $filters['division'] : 'Semua Divisi') : '-' }}<br>
            <strong>Tanggal Cetak:</strong> {{ now()->format('d-m-Y H:i') }}<br>
            <strong>Dicetak Oleh:</strong> {{ $printedBy }}
        </div>

        @if($isCompanyAssetSummary)
        <div class="meta" style="margin-bottom: 12px;">
            <strong>Total Jenis Barang:</strong> {{ $companySummaryTotals['item_groups'] }}<br>
            <strong>Total Unit:</strong> {{ $companySummaryTotals['total_unit'] }}<br>
            <strong>Unit Active:</strong> {{ $companySummaryTotals['unit_active'] }}<br>
            <strong>Unit Maintenance:</strong> {{ $companySummaryTotals['unit_maintenance'] }}<br>
            <strong>Unit Disposed:</strong> {{ $companySummaryTotals['unit_disposed'] }}
        </div>

        <table class="company-asset-summary-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Barang</th>
                    <th>Tipe</th>
                    <th>Total Unit</th>
                    <th>Active</th>
                    <th>Maintenance</th>
                    <th>Lokasi Penempatan</th>
                    <th>PIC / Kode Asset</th>
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
                    <td>{{ $row['no'] ?? '-' }}</td>
                    <td>{{ $row['nama_barang'] ?? '-' }}</td>
                    <td>{{ $row['tipe'] ?? '-' }}</td>
                    <td>{{ $row['total_unit'] ?? 0 }}</td>
                    <td>{{ $row['unit_active'] ?? 0 }}</td>
                    <td>{{ $row['unit_maintenance'] ?? 0 }}</td>
                    <td>
                        <div class="stacked-list">
                            @forelse($locations as $location)
                            <span>{{ $location }}</span>
                            @empty
                            <span>-</span>
                            @endforelse
                        </div>
                    </td>
                    <td>
                        <div class="stacked-list">
                            @forelse($pics as $index => $pic)
                            <span>{{ $pic }}{{ isset($assetCodes[$index]) ? ' (' . $assetCodes[$index] . ')' : '' }}</span>
                            @empty
                            <span>-</span>
                            @endforelse
                            @if($pics->isEmpty() && $assetCodes->isNotEmpty())
                                @foreach($assetCodes as $assetCode)
                                <span>{{ $assetCode }}</span>
                                @endforeach
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">Tidak ada data pada filter yang dipilih.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @elseif($isAssetManagementReport)
        <div class="meta" style="margin-bottom: 12px;">
            <strong>Kategori Asset:</strong> {{ $assetReportTypes[$reportType] }}<br>
            <strong>Total Unit:</strong> {{ $assetTotals['total_unit'] }}<br>
            <strong>Unit Active:</strong> {{ $assetTotals['unit_active'] }}<br>
            <strong>Unit Maintenance:</strong> {{ $assetTotals['unit_maintenance'] }}<br>
            <strong>Unit Disposed:</strong> {{ $assetTotals['unit_disposed'] }}
        </div>

        <table class="asset-management-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Asset</th>
                    <th>Penempatan</th>
                    <th>Status</th>
                    <th>Detail Asset</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportRowsCollection as $row)
                <tr>
                    <td>{{ $row['no'] ?? '-' }}</td>
                    <td>
                        <div class="stacked-list">
                            <span>{{ $row['nama_barang'] ?? '-' }}</span>
                            <span>{{ $row['kode_asset'] ?? '-' }}</span>
                            <span>{{ $row['tipe'] ?? '-' }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="stacked-list">
                            <span>{{ $row['lokasi'] ?? '-' }}</span>
                            <span>PIC: {{ $row['pic'] ?? '-' }}</span>
                        </div>
                    </td>
                    <td>{{ $row['status'] ?? '-' }}</td>
                    <td>
                        <div class="stacked-list">
                            <span>Nomor: {{ $row['nomor_identitas'] ?? '-' }}</span>
                            <span>Perolehan: {{ $row['tanggal_perolehan'] ?? '-' }}</span>
                            <span>Nilai: {{ $row['nilai_asset'] ?? '-' }}</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">Tidak ada data pada kategori asset ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @else
        <table>
            <thead>
                <tr>
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
                    <td colspan="{{ count($columns) }}">Tidak ada data pada filter yang dipilih.</td>
                </tr>
                @endforelse
                @if($isPurchaseOrderReport && $reportRowsCollection->isNotEmpty())
                <tr>
                    <th colspan="{{ max(count($columns) - 1, 1) }}" style="text-align: right;">Total Nilai Biaya</th>
                    <th>Rp {{ number_format($totalPurchaseOrderAmount, 0, ',', '.') }}</th>
                </tr>
                @endif
            </tbody>
        </table>
        @endif

        <div class="signature">
            <table class="signature-table">
                <tr>
                    <td></td>
                    <td class="signature-box">
                        Cirebon, {{ now()->translatedFormat('d F Y') }}<br>
                        Mengetahui,
                        <div class="signature-space"></div>
                        <strong>(________________________)</strong><br>
                        Manager Operasional
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer-accent"></div>
    </div>
</body>
</html>
