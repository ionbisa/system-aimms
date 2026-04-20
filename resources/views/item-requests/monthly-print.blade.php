<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Bulanan Permintaan Barang - {{ $selectedMonth->format('Y-m') }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #222; }
        h1, h2, h3, p { margin: 0; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #cfcfcf; padding: 6px; vertical-align: top; }
        th { background: #f1f1f1; text-align: center; }
        .header { border-bottom: 2px solid #222; padding-bottom: 14px; margin-bottom: 18px; }
        .header-table, .signature-table { width: 100%; border: 0; border-collapse: collapse; }
        .header-table td, .signature-table td { border: 0; padding: 0; vertical-align: top; }
        .logo-cell { width: 90px; }
        .logo { width: 72px; height: 72px; object-fit: contain; }
        .company-name { font-size: 22px; font-weight: 700; }
        .company-subtitle { font-size: 14px; font-weight: 700; margin-top: 2px; }
        .company-address { font-size: 12px; color: #555; margin-top: 6px; line-height: 1.5; }
        .report-title { text-align: center; margin-bottom: 14px; }
        .report-title h2 { font-size: 18px; text-transform: uppercase; }
        .meta { margin-bottom: 16px; font-size: 12px; line-height: 1.7; }
        .signature { margin-top: 28px; font-size: 12px; }
        .signature-box { width: 220px; text-align: center; }
        .signature-space { height: 64px; }
        .actions { margin-bottom: 16px; }
        @media print {
            .actions { display: none; }
            body { margin: 12px; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

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
        <h2>Laporan Bulanan Permintaan Barang</h2>
        <p>Rekap pengajuan, approval, dan distribusi barang per bulan</p>
    </div>

    <div class="meta">
        <strong>Periode:</strong> {{ $selectedMonth->translatedFormat('F Y') }}<br>
        <strong>Filter Status:</strong> {{ $status !== '' ? ucfirst($status) : 'Semua Status' }}<br>
        <strong>Pencarian:</strong> {{ $search !== '' ? $search : '-' }}<br>
        <strong>Tanggal Cetak:</strong> {{ now()->format('d-m-Y H:i') }}<br>
        <strong>Dicetak Oleh:</strong> {{ auth()->user()->name ?? 'User' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No Permintaan</th>
                <th>Tanggal</th>
                <th>Divisi</th>
                <th>Pemohon</th>
                <th>Barang</th>
                <th>Status</th>
                <th>Tahap</th>
                <th>Realisasi</th>
                <th>Stok Terpotong</th>
            </tr>
        </thead>
        <tbody>
            @forelse($itemRequests as $index => $itemRequest)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $itemRequest->request_number }}</td>
                <td>{{ optional($itemRequest->requested_at)->format('d-m-Y H:i') }}</td>
                <td>{{ $itemRequest->division }}</td>
                <td>{{ $itemRequest->requester?->name ?? '-' }}</td>
                <td>
                    @foreach($itemRequest->items as $item)
                    <div>{{ $item->item_name }} ({{ rtrim(rtrim(number_format((float) $item->qty, 2, '.', ''), '0'), '.') }} {{ $item->unit }})</div>
                    @endforeach
                </td>
                <td>{{ $itemRequest->status_label }}</td>
                <td>{{ $itemRequest->current_step_label }}</td>
                <td>{{ $itemRequest->realization_label }}</td>
                <td>{{ $itemRequest->stock_deducted_at ? $itemRequest->stock_deducted_at->format('d-m-Y H:i') : '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align: center;">Tidak ada data permintaan barang pada filter yang dipilih.</td>
            </tr>
            @endforelse
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
</body>
</html>
