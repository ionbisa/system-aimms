@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Form Permintaan Barang</h4>
            <small class="text-muted">Tanggal dan waktu dibuat otomatis saat form disimpan.</small>
        </div>
        <a href="{{ route('item-requests.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            @if(!($supportsStockSelection ?? false) || !($supportsProcurementType ?? false))
            <div class="alert alert-warning">
                Form tetap bisa dipakai, tetapi fitur pilihan stok langsung dan jenis permintaan penuh baru aktif setelah migration terbaru dijalankan.
            </div>
            @endif

            <form action="{{ route('item-requests.store') }}" method="POST" id="itemRequestForm">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Judul</label>
                        <input type="text" class="form-control" value="PERMINTAAN BARANG" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal dan Waktu</label>
                        <input type="text" class="form-control" value="{{ now()->format('d-m-Y H:i') }} (otomatis saat submit)" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Divisi</label>
                        <input type="text" name="division" value="{{ old('division') }}" class="form-control" placeholder="Contoh: Produksi Basreng / Operasional Kendaraan" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Catatan Awal</label>
                        <input type="text" name="initial_note" value="{{ old('initial_note') }}" class="form-control" placeholder="Opsional, misalnya kebutuhan mendesak atau jadwal distribusi">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Daftar Barang</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addItemRow">Tambah Baris</button>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle" id="itemRequestItemsTable">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th style="width: 60px;">No</th>
                                @if($supportsStockSelection ?? false)
                                <th>Pilih dari On Stock</th>
                                @endif
                                @if($supportsProcurementType ?? false)
                                <th style="width: 170px;">Jenis Permintaan</th>
                                @endif
                                <th>Nama Barang</th>
                                <th style="width: 140px;">Qty</th>
                                <th style="width: 140px;">Satuan</th>
                                <th style="width: 180px;">Indikator Stok</th>
                                <th>Keterangan Penggunaan</th>
                                <th style="width: 80px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center row-number">1</td>
                                @if($supportsStockSelection ?? false)
                                <td>
                                    <select name="items[0][stock_id]" class="form-select stock-selector">
                                        <option value="">Tidak pilih stok / barang baru</option>
                                        @foreach($stocks as $stock)
                                        <option value="{{ $stock->id }}" data-item-name="{{ $stock->item_name }}" data-unit="{{ $stock->unit }}" data-stock-qty="{{ $stock->qty }}">
                                            {{ $stock->item_code ?: '-' }} - {{ $stock->item_name }} (stok: {{ $stock->qty }} {{ $stock->unit ?: '' }})
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                @endif
                                @if($supportsProcurementType ?? false)
                                <td>
                                    <select name="items[0][procurement_type]" class="form-select procurement-type">
                                        <option value="stock_distribution">Distribusi Stok</option>
                                        <option value="purchase_request">Permintaan Pembelian</option>
                                    </select>
                                </td>
                                @endif
                                <td><input type="text" name="items[0][item_name]" class="form-control item-name-input" required></td>
                                <td><input type="number" step="1" min="1" name="items[0][qty]" class="form-control qty-input" required></td>
                                <td><input type="text" name="items[0][unit]" class="form-control unit-input" placeholder="PCS / BOX / Roll" required></td>
                                <td>
                                    <div class="stock-indicator small text-muted">
                                        <span class="badge text-bg-secondary">Belum dicek</span>
                                        <span class="indicator-text ms-1">Pilih stok untuk melihat ketersediaan.</span>
                                    </div>
                                </td>
                                <td><textarea name="items[0][description]" class="form-control" rows="2" placeholder="Jelaskan digunakan dimana, untuk apa, penggantian rusak atau habis pakai"></textarea></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-row">Hapus</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <strong>Dibuat Oleh</strong><br>
                            <small class="text-muted">Admin Produksi / SPV Operasional</small><br>
                            <small class="text-muted">Tanggal & waktu otomatis</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <strong>Diketahui Oleh</strong><br>
                            <small class="text-muted">Kepala Produksi</small><br>
                            <small class="text-muted">Diisi saat approval digital</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <strong>Disetujui Oleh</strong><br>
                            <small class="text-muted">Manager Operasional</small><br>
                            <small class="text-muted">Diisi saat approval digital</small>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary">Simpan Permintaan Barang</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tableBody = document.querySelector('#itemRequestItemsTable tbody');
        const addRowButton = document.getElementById('addItemRow');

        const renumberRows = () => {
            Array.from(tableBody.querySelectorAll('tr')).forEach((row, index) => {
                row.querySelector('.row-number').textContent = index + 1;

                row.querySelectorAll('input, textarea').forEach((field) => {
                    field.name = field.name.replace(/items\[\d+\]/, 'items[' + index + ']');
                });
            });
        };

        addRowButton.addEventListener('click', function () {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="text-center row-number"></td>
                @if($supportsStockSelection ?? false)
                <td>
                    <select name="items[0][stock_id]" class="form-select stock-selector">
                        <option value="">Tidak pilih stok / barang baru</option>
                        @foreach($stocks as $stock)
                        <option value="{{ $stock->id }}" data-item-name="{{ $stock->item_name }}" data-unit="{{ $stock->unit }}" data-stock-qty="{{ $stock->qty }}">
                            {{ $stock->item_code ?: '-' }} - {{ $stock->item_name }} (stok: {{ $stock->qty }} {{ $stock->unit ?: '' }})
                        </option>
                        @endforeach
                    </select>
                </td>
                @endif
                @if($supportsProcurementType ?? false)
                <td>
                    <select name="items[0][procurement_type]" class="form-select procurement-type">
                        <option value="stock_distribution">Distribusi Stok</option>
                        <option value="purchase_request">Permintaan Pembelian</option>
                    </select>
                </td>
                @endif
                <td><input type="text" name="items[0][item_name]" class="form-control item-name-input" required></td>
                <td><input type="number" step="1" min="1" name="items[0][qty]" class="form-control qty-input" required></td>
                <td><input type="text" name="items[0][unit]" class="form-control unit-input" required></td>
                <td>
                    <div class="stock-indicator small text-muted">
                        <span class="badge text-bg-secondary">Belum dicek</span>
                        <span class="indicator-text ms-1">Pilih stok untuk melihat ketersediaan.</span>
                    </div>
                </td>
                <td><textarea name="items[0][description]" class="form-control" rows="2" placeholder="Jelaskan digunakan dimana, untuk apa, penggantian rusak atau habis pakai"></textarea></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">Hapus</button></td>
            `;

            tableBody.appendChild(row);
            renumberRows();
            syncStockIndicator(row);
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
        });

        tableBody.addEventListener('change', function (event) {
            if (event.target.classList.contains('procurement-type')) {
                syncStockIndicator(event.target.closest('tr'));
                syncSubmitState();
                return;
            }

            if (event.target.classList.contains('qty-input')) {
                syncStockIndicator(event.target.closest('tr'));
                syncSubmitState();
                return;
            }

            if (!event.target.classList.contains('stock-selector')) {
                return;
            }

            const selector = event.target;
            const row = selector.closest('tr');
            const selectedOption = selector.options[selector.selectedIndex];
            const itemNameInput = row.querySelector('.item-name-input');
            const unitInput = row.querySelector('.unit-input');

            if (!selectedOption || selectedOption.value === '') {
                syncStockIndicator(row);
                syncSubmitState();
                return;
            }

            itemNameInput.value = selectedOption.dataset.itemName ?? itemNameInput.value;
            unitInput.value = selectedOption.dataset.unit ?? unitInput.value;
            syncStockIndicator(row);
            syncSubmitState();
        });

        tableBody.addEventListener('input', function (event) {
            if (!event.target.classList.contains('qty-input')) {
                return;
            }

            syncStockIndicator(event.target.closest('tr'));
            syncSubmitState();
        });

        const syncStockIndicator = (row) => {
            if (!row) {
                return;
            }

            const indicator = row.querySelector('.stock-indicator');
            const selector = row.querySelector('.stock-selector');
            const qtyInput = row.querySelector('.qty-input');
            const procurementType = row.querySelector('.procurement-type');
            const badge = indicator?.querySelector('.badge');
            const text = indicator?.querySelector('.indicator-text');

            if (!indicator || !qtyInput || !badge || !text) {
                return;
            }

            const selectedOption = selector?.options?.[selector.selectedIndex];
            const requestedQty = Number(qtyInput.value || 0);
            const procurementValue = procurementType?.value ?? 'stock_distribution';

            indicator.className = 'stock-indicator small';
            badge.className = 'badge';

            if (!selector || !selectedOption || selectedOption.value === '') {
                indicator.classList.add('text-muted');
                badge.classList.add('text-bg-secondary');
                badge.textContent = 'Belum dicek';
                text.textContent = procurementValue === 'purchase_request'
                    ? 'Item diarahkan sebagai permintaan pembelian.'
                    : 'Belum pilih stok. Untuk distribusi stok, silakan pilih barang BRG terlebih dahulu.';
                return;
            }

            const stockQty = Number(selectedOption.dataset.stockQty || 0);
            const unit = selectedOption.dataset.unit || '';

            if (requestedQty <= 0) {
                indicator.classList.add('text-muted');
                badge.classList.add('text-bg-secondary');
                badge.textContent = 'Isi Qty';
                text.textContent = 'Masukkan qty untuk membandingkan dengan stok tersedia ' + stockQty + ' ' + unit + '.';
                return;
            }

            if (stockQty >= requestedQty) {
                indicator.classList.add('text-success', 'fw-semibold');
                badge.classList.add('text-bg-success');
                badge.textContent = 'Stok Tersedia';
                text.textContent = 'Tersedia ' + stockQty + ' ' + unit + ', permintaan ' + requestedQty + ' ' + unit + '.';
                return;
            }

            if (procurementValue === 'purchase_request') {
                indicator.classList.add('text-warning', 'fw-semibold');
                badge.classList.add('text-bg-warning');
                badge.textContent = 'Alih ke Pembelian';
                text.textContent = 'Stok hanya ' + stockQty + ' ' + unit + ', permintaan ' + requestedQty + ' ' + unit + '. Item ini akan diproses sebagai permintaan pembelian.';
                return;
            }

            indicator.classList.add('text-danger', 'fw-semibold');
            badge.classList.add('text-bg-danger');
            badge.textContent = 'Stok Kurang';
            text.textContent = 'Tersedia ' + stockQty + ' ' + unit + ', permintaan ' + requestedQty + ' ' + unit + '. Ubah ke permintaan pembelian atau sesuaikan qty.';
        };

        const syncSubmitState = () => {
            const submitButton = document.querySelector('#itemRequestForm button[type="submit"], #itemRequestForm .btn-primary');
            const rows = Array.from(tableBody.querySelectorAll('tr'));

            const hasBlockingRow = rows.some((row) => {
                const selector = row.querySelector('.stock-selector');
                const qtyInput = row.querySelector('.qty-input');
                const procurementType = row.querySelector('.procurement-type');

                if (!qtyInput) {
                    return false;
                }

                if (!selector || !procurementType) {
                    return false;
                }

                const selectedOption = selector.options[selector.selectedIndex];
                if (!selectedOption || selectedOption.value === '' || procurementType.value === 'purchase_request') {
                    return false;
                }

                const stockQty = Number(selectedOption.dataset.stockQty || 0);
                const requestedQty = Number(qtyInput.value || 0);

                return requestedQty > 0 && stockQty < requestedQty;
            });

            if (!submitButton) {
                return;
            }

            submitButton.disabled = hasBlockingRow;
            submitButton.textContent = hasBlockingRow
                ? 'Perbaiki Item Stok Kurang Dulu'
                : 'Simpan Permintaan Barang';
        };

        Array.from(tableBody.querySelectorAll('tr')).forEach(syncStockIndicator);
        renumberRows();
        syncSubmitState();
    });
</script>
@endsection
