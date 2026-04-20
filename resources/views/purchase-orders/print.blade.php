<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order - {{ $purchaseOrder->po_number }}</title>
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
        .info-table td { padding-bottom: 6px; }
        .signature { margin-top: 32px; font-size: 12px; }
        .signature-box { width: 25%; text-align: center; }
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
                <td class="logo-cell"><img src="{{ asset('assets/img/logo.png') }}" alt="Bangga Group" class="logo"></td>
                <td>
                    <div class="company-name">BANGGA GROUP</div>
                    <div class="company-subtitle">Asset Inventory Maintenance and Management System</div>
                    <div class="company-address">Blok Capar 3 RT. 017 RW. 009 Desa Sidangwangi Kec. Sumber Kab. Cirebon 45611<br>Telp. 02318858881 HP. 081221703904 Email : Office@banggagroup.com</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="doc-title">
        <h2>Permintaan Pembelian / Pembayaran / Perbaikan / BBM</h2>
        <p>Dokumen pengajuan Purchase Order</p>
    </div>

    <div class="meta">
        <table class="info-table">
            <tr>
                <td style="width:25%;"><strong>No PO</strong></td>
                <td style="width:25%;">: {{ $purchaseOrder->po_number }}</td>
                <td style="width:25%;"><strong>Tanggal</strong></td>
                <td style="width:25%;">: {{ optional($purchaseOrder->transaction_date)->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <td><strong>Jenis</strong></td>
                <td>: {{ $purchaseOrder->transaction_type }}</td>
                <td><strong>Divisi</strong></td>
                <td>: {{ $purchaseOrder->division }}</td>
            </tr>
            <tr>
                <td><strong>Kategori</strong></td>
                <td>: {{ $purchaseOrder->category ?: '-' }}</td>
                <td><strong>Vendor</strong></td>
                <td>: {{ $purchaseOrder->vendor ?: '-' }}</td>
            </tr>
            <tr>
                <td><strong>Dibuat Oleh</strong></td>
                <td>: {{ $purchaseOrder->requester?->name ?? '-' }}</td>
                <td><strong>Total Estimasi</strong></td>
                <td>: Rp {{ number_format((float) $purchaseOrder->total_price, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Total Realisasi</strong></td>
                <td>: Rp {{ number_format((float) $purchaseOrder->effective_total_price, 0, ',', '.') }}</td>
                <td><strong>Selisih</strong></td>
                <td>: {{ $purchaseOrder->price_variance > 0 ? '+' : '' }}Rp {{ number_format((float) $purchaseOrder->price_variance, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Status</strong></td>
                <td>: {{ $purchaseOrder->display_status }}</td>
                <td><strong>Realisasi</strong></td>
                <td>: {{ $purchaseOrder->realization_label }}</td>
            </tr>
            <tr>
                <td><strong>Uraian</strong></td>
                <td colspan="3">: {{ $purchaseOrder->description ?: '-' }}</td>
            </tr>
            <tr>
                <td><strong>Catatan Admin GA</strong></td>
                <td colspan="3">: {{ $purchaseOrder->receipt_note ?: '-' }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:6%;">No</th>
                <th>Nama Barang / Jasa</th>
                <th style="width:10%;">Qty</th>
                <th style="width:10%;">Satuan</th>
                <th style="width:14%;">Estimasi Harga Satuan</th>
                <th style="width:14%;">Jumlah Harga</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $item)
            <tr>
                <td>{{ $item->line_number }}</td>
                <td>{{ $item->item_name }}</td>
                <td>{{ rtrim(rtrim(number_format((float) $item->qty, 2, '.', ''), '0'), '.') }}</td>
                <td>{{ $item->unit }}</td>
                <td>Rp {{ number_format((float) $item->estimated_unit_price, 0, ',', '.') }}</td>
                <td>Rp {{ number_format((float) $item->estimated_total_price, 0, ',', '.') }}</td>
                <td>{{ $item->description ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right;"><strong>Total Estimasi</strong></td>
                <td><strong>Rp {{ number_format((float) $purchaseOrder->total_price, 0, ',', '.') }}</strong></td>
                <td></td>
            </tr>
            <tr>
                <td colspan="5" style="text-align:right;"><strong>Total Realisasi</strong></td>
                <td><strong>Rp {{ number_format((float) $purchaseOrder->effective_total_price, 0, ',', '.') }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    @php
        $operationalManager = $purchaseOrder->approvals->firstWhere('stage_key', 'operational_manager');
        $director = $purchaseOrder->approvals->firstWhere('stage_key', 'director_operational');
    @endphp

    <div class="signature">
        <table class="signature-table">
            <tr>
                <td class="signature-box">
                    Dibuat Oleh,<br>Admin GA
                    <div class="signature-space"></div>
                    <strong>{{ $purchaseOrder->requester?->name ?? '(__________________)' }}</strong><br>
                    {{ optional($purchaseOrder->created_at)->format('d-m-Y H:i') }}
                </td>
                <td class="signature-box">
                    Diketahui Oleh,<br>Manager Operasional
                    <div class="signature-space"></div>
                    <strong>{{ $operationalManager?->actor?->name ?? '(__________________)' }}</strong><br>
                    {{ $operationalManager?->acted_at?->format('d-m-Y H:i') ?? '-' }}
                </td>
                <td class="signature-box">
                    Disetujui Oleh,<br>Direktur Operasional
                    <div class="signature-space"></div>
                    <strong>{{ $director?->actor?->name ?? '(__________________)' }}</strong><br>
                    {{ $director?->acted_at?->format('d-m-Y H:i') ?? '-' }}
                </td>
                <td class="signature-box">
                    Realisasi,<br>Manager Finance
                    <div class="signature-space"></div>
                    <strong>{{ $purchaseOrder->realizer?->name ?? '(__________________)' }}</strong><br>
                    {{ $purchaseOrder->realized_at?->format('d-m-Y H:i') ?? '-' }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
