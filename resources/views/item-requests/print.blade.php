<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Permintaan Barang - {{ $itemRequest->request_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #222; }
        h1, h2, h3, p { margin: 0; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #cfcfcf; padding: 8px; vertical-align: top; }
        th { background: #f3f3f3; text-align: left; }
        .header { border-bottom: 2px solid #222; padding-bottom: 14px; margin-bottom: 18px; }
        .header-table, .signature-table, .info-table { width: 100%; border: 0; border-collapse: collapse; }
        .header-table td, .signature-table td, .info-table td { border: 0; padding: 0; vertical-align: top; }
        .logo-cell { width: 90px; }
        .logo { width: 72px; height: 72px; object-fit: contain; }
        .company-name { font-size: 22px; font-weight: 700; }
        .company-subtitle { font-size: 14px; font-weight: 700; margin-top: 2px; }
        .company-address { font-size: 12px; color: #555; margin-top: 6px; line-height: 1.5; }
        .doc-title { text-align: center; margin: 18px 0; }
        .doc-title h2 { font-size: 18px; text-transform: uppercase; }
        .meta { margin-bottom: 16px; font-size: 12px; line-height: 1.7; }
        .signature { margin-top: 32px; font-size: 12px; }
        .signature-box { width: 33.33%; text-align: center; }
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

    <div class="doc-title">
        <h2>Permintaan Barang</h2>
        <p>Dokumen pengajuan, approval, dan realisasi distribusi barang</p>
    </div>

    <div class="meta">
        <table class="info-table">
            <tr>
                <td style="width: 25%;"><strong>No Permintaan</strong></td>
                <td style="width: 25%;">: {{ $itemRequest->request_number }}</td>
                <td style="width: 25%;"><strong>Tanggal dan Waktu</strong></td>
                <td style="width: 25%;">: {{ optional($itemRequest->requested_at)->format('d-m-Y H:i') }}</td>
            </tr>
            <tr>
                <td><strong>Divisi</strong></td>
                <td>: {{ $itemRequest->division }}</td>
                <td><strong>Status</strong></td>
                <td>: {{ $itemRequest->status_label }}</td>
            </tr>
            <tr>
                <td><strong>Dibuat Oleh</strong></td>
                <td>: {{ $itemRequest->requester?->name ?? '-' }} ({{ $itemRequest->requested_role ?? '-' }})</td>
                <td><strong>Realisasi</strong></td>
                <td>: {{ $itemRequest->realization_label }}</td>
            </tr>
            <tr>
                <td><strong>Stok Terpotong</strong></td>
                <td>: {{ $itemRequest->stock_deducted_at ? $itemRequest->stock_deducted_at->format('d-m-Y H:i') : '-' }}</td>
                <td><strong>Distribusi Selesai</strong></td>
                <td>: {{ $itemRequest->completed_at ? $itemRequest->completed_at->format('d-m-Y H:i') : '-' }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th>Nama Barang</th>
                <th style="width: 12%;">Qty</th>
                <th style="width: 12%;">Satuan</th>
                <th style="width: 22%;">Stok Dipakai</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($itemRequest->items as $item)
            <tr>
                <td>{{ $item->line_number }}</td>
                <td>{{ $item->item_name }}</td>
                <td>{{ rtrim(rtrim(number_format((float) $item->qty, 2, '.', ''), '0'), '.') }}</td>
                <td>{{ $item->unit }}</td>
                <td>{{ $item->stock?->item_name ? $item->stock->item_name . ' (' . ($item->stock->item_code ?: '-') . ')' : '-' }}</td>
                <td>{{ $item->description ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="meta" style="margin-top: 16px;">
        <strong>Catatan Awal:</strong> {{ $itemRequest->initial_note ?: '-' }}<br>
        <strong>Catatan Realisasi GA:</strong> {{ $itemRequest->realization_note ?: '-' }}
    </div>

    @php
        $productionHead = $itemRequest->approvals->firstWhere('stage_key', 'production_head');
        $operationalManager = $itemRequest->approvals->firstWhere('stage_key', 'operational_manager');
    @endphp

    <div class="signature">
        <table class="signature-table">
            <tr>
                <td class="signature-box">
                    Dibuat Oleh,<br>
                    Admin Produksi / SPV Operasional
                    <div class="signature-space"></div>
                    <strong>{{ $itemRequest->requester?->name ?? '(__________________)' }}</strong><br>
                    {{ optional($itemRequest->requested_at)->format('d-m-Y H:i') }}
                </td>
                <td class="signature-box">
                    Diketahui Oleh,<br>
                    Kepala Produksi
                    <div class="signature-space"></div>
                    <strong>{{ $productionHead?->actor?->name ?? '(__________________)' }}</strong><br>
                    {{ $productionHead?->acted_at?->format('d-m-Y H:i') ?? '-' }}
                </td>
                <td class="signature-box">
                    Disetujui Oleh,<br>
                    Manager Operasional
                    <div class="signature-space"></div>
                    <strong>{{ $operationalManager?->actor?->name ?? '(__________________)' }}</strong><br>
                    {{ $operationalManager?->acted_at?->format('d-m-Y H:i') ?? '-' }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
