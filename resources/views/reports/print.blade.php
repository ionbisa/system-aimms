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
            'nama_barang' => 'Nama Barang',
            'lokasi' => 'Lokasi',
            'status' => 'Status',
            'tanggal_update' => 'Tanggal Update',
        ];

        $documentNumber = 'DOC/' . strtoupper(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 ]/', '', $title))) . '/' . now()->format('Ymd') . '/' . now()->format('His');
        $totalPurchaseOrderAmount = ($filters['type'] ?? '') === 'purchase_orders'
            ? collect($reportRows)->reduce(function ($carry, $row) {
                $numericValue = preg_replace('/[^0-9]/', '', (string) ($row['total_harga'] ?? '0'));

                return $carry + (float) $numericValue;
            }, 0)
            : 0;
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
            <strong>Jenis Data:</strong> {{ str_replace('_', ' ', $filters['type']) }}<br>
            <strong>Periode:</strong> {{ \Carbon\Carbon::parse($filters['start_date'])->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($filters['end_date'])->format('d-m-Y') }}<br>
            <strong>Divisi:</strong> {{ ($filters['division'] ?? '') !== '' ? $filters['division'] : 'Semua Divisi' }}<br>
            <strong>Tanggal Cetak:</strong> {{ now()->format('d-m-Y H:i') }}<br>
            <strong>Dicetak Oleh:</strong> {{ $printedBy }}
        </div>

        <table>
            <thead>
                <tr>
                    @foreach($columns as $column)
                    <th>{{ $columnLabels[$column] ?? ucfirst(str_replace('_', ' ', $column)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($reportRows as $row)
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
                @if(($filters['type'] ?? '') === 'purchase_orders' && count($reportRows) > 0)
                <tr>
                    <th colspan="{{ max(count($columns) - 1, 1) }}" style="text-align: right;">Total Nilai Biaya</th>
                    <th>Rp {{ number_format($totalPurchaseOrderAmount, 0, ',', '.') }}</th>
                </tr>
                @endif
            </tbody>
        </table>

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
