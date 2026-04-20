@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Form Purchase Order</h4>
            <small class="text-muted">Tanggal dan waktu dibuat otomatis saat form disimpan oleh Admin GA.</small>
        </div>
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('purchase-orders.store') }}" method="POST" id="purchaseOrderForm">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Judul</label>
                        <input type="text" class="form-control" value="PERMINTAAN PEMBELIAN BARANG / PEMBAYARAN JASA / PERBAIKAN BARANG / PEMBELIAN BBM" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal dan Waktu</label>
                        <input type="text" class="form-control" value="{{ now()->format('d-m-Y H:i') }} (otomatis saat submit)" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jenis Permintaan</label>
                        <select name="transaction_type" class="form-select" required>
                            <option value="">Pilih jenis permintaan</option>
                            @foreach($transactionTypes as $transactionType)
                            <option value="{{ $transactionType }}" @selected(old('transaction_type') === $transactionType)>{{ $transactionType }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Divisi</label>
                        <select name="division" class="form-select" required>
                            <option value="">Pilih divisi</option>
                            @foreach($divisions as $division)
                            <option value="{{ $division }}" @selected(old('division') === $division)>{{ $division }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kategori</label>
                        <input type="text" name="category" value="{{ old('category') }}" class="form-control" placeholder="Contoh: Operasional, Produksi, Maintenance" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vendor</label>
                        <input type="text" name="vendor" value="{{ old('vendor') }}" class="form-control" placeholder="Nama vendor, toko, bengkel, atau penyedia jasa" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Uraian</label>
                        <input type="text" name="description" value="{{ old('description') }}" class="form-control" placeholder="Tujuan pengajuan, sama fungsi dengan uraian di reporting" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan Awal</label>
                        <input type="text" name="initial_note" value="{{ old('initial_note') }}" class="form-control" placeholder="Opsional, misalnya pembelian mendesak, pembayaran vendor, atau jadwal service">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Detail Pengajuan</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addPurchaseOrderItemRow">Tambah Baris</button>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle" id="purchaseOrderItemsTable">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th style="width: 60px;">No</th>
                                <th>Nama Barang / Jasa</th>
                                <th style="width: 120px;">Qty</th>
                                <th style="width: 120px;">Satuan</th>
                                <th style="width: 180px;">Estimasi Harga Satuan</th>
                                <th style="width: 180px;">Jumlah Harga</th>
                                <th>Keterangan</th>
                                <th style="width: 80px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center row-number">1</td>
                                <td><input type="text" name="items[0][item_name]" class="form-control" required></td>
                                <td><input type="number" step="0.01" min="0.01" name="items[0][qty]" class="form-control qty-input" required></td>
                                <td><input type="text" name="items[0][unit]" class="form-control" required></td>
                                <td><input type="number" step="0.01" min="0" name="items[0][estimated_unit_price]" class="form-control unit-price-input" required></td>
                                <td><input type="text" class="form-control line-total-display" value="Rp 0" readonly></td>
                                <td><textarea name="items[0][description]" class="form-control" rows="2" placeholder="Tujuan penggunaan, pembayaran apa, perbaikan apa, atau kebutuhan BBM"></textarea></td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">Hapus</button></td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="5" class="text-end fw-semibold">Total Harga</td>
                                <td><input type="text" id="purchaseOrderGrandTotal" class="form-control fw-bold" value="Rp 0" readonly></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <strong>Dibuat Oleh</strong><br>
                            <small class="text-muted">Admin GA</small><br>
                            <small class="text-muted">Tanggal & waktu otomatis</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <strong>Diketahui Oleh</strong><br>
                            <small class="text-muted">Manager Operasional</small><br>
                            <small class="text-muted">Diisi saat approval digital</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <strong>Disetujui Oleh</strong><br>
                            <small class="text-muted">Direktur Operasional</small><br>
                            <small class="text-muted">Diisi saat approval digital</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <strong>Realisasi</strong><br>
                            <small class="text-muted">Manager Finance</small><br>
                            <small class="text-muted">Diisi saat realisasi</small>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary">Simpan Purchase Order</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tableBody = document.querySelector('#purchaseOrderItemsTable tbody');
        const addRowButton = document.getElementById('addPurchaseOrderItemRow');
        const grandTotalInput = document.getElementById('purchaseOrderGrandTotal');

        const formatRupiah = (number) => {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(number || 0));
        };

        const roundToTwoDecimals = (number) => {
            return Math.round((Number(number || 0) + Number.EPSILON) * 100) / 100;
        };

        const roundToRupiah = (number) => {
            return Math.round(Number(number || 0));
        };

        const renumberRows = () => {
            Array.from(tableBody.querySelectorAll('tr')).forEach((row, index) => {
                row.querySelector('.row-number').textContent = index + 1;

                row.querySelectorAll('input, textarea').forEach((field) => {
                    if (!field.name) {
                        return;
                    }

                    field.name = field.name.replace(/items\[\d+\]/, 'items[' + index + ']');
                });
            });
        };

        const syncTotals = () => {
            let grandTotal = 0;

            Array.from(tableBody.querySelectorAll('tr')).forEach((row) => {
                const qty = roundToTwoDecimals(row.querySelector('.qty-input')?.value || 0);
                const unitPrice = roundToRupiah(row.querySelector('.unit-price-input')?.value || 0);
                const lineTotal = roundToRupiah(qty * unitPrice);

                grandTotal = roundToRupiah(grandTotal + lineTotal);

                const lineDisplay = row.querySelector('.line-total-display');
                if (lineDisplay) {
                    lineDisplay.value = formatRupiah(lineTotal);
                }
            });

            if (grandTotalInput) {
                grandTotalInput.value = formatRupiah(grandTotal);
            }
        };

        addRowButton.addEventListener('click', function () {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="text-center row-number"></td>
                <td><input type="text" name="items[0][item_name]" class="form-control" required></td>
                <td><input type="number" step="0.01" min="0.01" name="items[0][qty]" class="form-control qty-input" required></td>
                <td><input type="text" name="items[0][unit]" class="form-control" required></td>
                <td><input type="number" step="0.01" min="0" name="items[0][estimated_unit_price]" class="form-control unit-price-input" required></td>
                <td><input type="text" class="form-control line-total-display" value="Rp 0" readonly></td>
                <td><textarea name="items[0][description]" class="form-control" rows="2"></textarea></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">Hapus</button></td>
            `;

            tableBody.appendChild(row);
            renumberRows();
            syncTotals();
        });

        tableBody.addEventListener('click', function (event) {
            if (!event.target.classList.contains('remove-row')) {
                return;
            }

            if (tableBody.querySelectorAll('tr').length === 1) {
                return;
            }

            event.target.closest('tr').remove();
            renumberRows();
            syncTotals();
        });

        tableBody.addEventListener('input', function (event) {
            if (!event.target.classList.contains('qty-input') && !event.target.classList.contains('unit-price-input')) {
                return;
            }

            syncTotals();
        });

        renumberRows();
        syncTotals();
    });
</script>
@endsection
