@extends('layouts.app')

@section('content')
<div class="container">
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
        <div>
            <h4 class="mb-1">Detail Permintaan Barang</h4>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge {{ $itemRequest->status_badge_class }}">{{ $itemRequest->status_label }}</span>
                <span class="badge text-bg-light">{{ $itemRequest->current_step_label }}</span>
                <span class="badge text-bg-info">{{ $itemRequest->realization_label }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('item-requests.print', $itemRequest) }}" target="_blank" class="btn btn-danger">Cetak / Simpan PDF</a>
            <a href="{{ route('item-requests.index', ['month' => optional($itemRequest->requested_at)->format('Y-m')]) }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Informasi Form</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Judul</small>
                            <strong>PERMINTAAN BARANG</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Tanggal dan Waktu</small>
                            <strong>{{ optional($itemRequest->requested_at)->format('d-m-Y H:i') }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">No Permintaan</small>
                            <strong>{{ $itemRequest->request_number }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Divisi</small>
                            <strong>{{ $itemRequest->division }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Dibuat Oleh</small>
                            <strong>{{ $itemRequest->requester?->name ?? '-' }}</strong><br>
                            <small class="text-muted">{{ $itemRequest->requested_role ?? '-' }}</small>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Lama Proses</small>
                            <strong>{{ optional($itemRequest->requested_at)?->diffForHumans(now(), true) ?? '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Stok Otomatis Berkurang</small>
                            <strong>{{ $itemRequest->stock_deducted_at ? $itemRequest->stock_deducted_at->format('d-m-Y H:i') : 'Belum terpotong' }}</strong>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Catatan Awal</small>
                            <div>{{ $itemRequest->initial_note ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Daftar Barang</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>No</th>
                                    <th>Nama Barang</th>
                                    <th>Qty</th>
                                    <th>Satuan</th>
                                    <th>Jenis Pemenuhan</th>
                                    <th>Stok Dipakai</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($itemRequest->items as $item)
                                <tr>
                                    <td class="text-center">{{ $item->line_number }}</td>
                                    <td>{{ $item->item_name }}</td>
                                    <td class="text-center">{{ rtrim(rtrim(number_format((float) $item->qty, 2, '.', ''), '0'), '.') }}</td>
                                    <td class="text-center">{{ $item->unit }}</td>
                                    <td class="text-center">{{ $item->procurement_type_label }}</td>
                                    <td>{{ ($item->procurement_type ?? 'stock_distribution') === 'purchase_request' ? 'Diproses pembelian' : ($item->stock?->item_name ? $item->stock->item_name . ' (' . ($item->stock->item_code ?: '-') . ')' : '-') }}</td>
                                    <td>{{ $item->description ?: '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Riwayat & Catatan</h5>
                        <small class="text-muted">Semua user terkait bisa memantau proses dari sini.</small>
                    </div>

                    <form action="{{ route('item-requests.comment', $itemRequest) }}" method="POST" class="row g-2 mb-3">
                        @csrf
                        <div class="col-md-10">
                            <input type="text" name="note" class="form-control" placeholder="Tambahkan catatan proses, kendala stok, atau informasi distribusi" required>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100">Kirim Catatan</button>
                        </div>
                    </form>

                    <div class="list-group">
                        @forelse($itemRequest->notes as $note)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <strong>{{ $note->actor_name ?: 'System' }}</strong>
                                    <small class="text-muted">({{ $note->actor_role ?: 'System' }})</small>
                                    <div>{{ $note->note }}</div>
                                </div>
                                <small class="text-muted text-nowrap">{{ $note->created_at->format('d-m-Y H:i') }}</small>
                            </div>
                        </div>
                        @empty
                        <div class="list-group-item text-muted">Belum ada riwayat catatan.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Jalur Approval</h5>
                    @foreach($itemRequest->approvals as $approval)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <strong>{{ $approval->stage_label }}</strong><br>
                                <small class="text-muted">{{ $approval->role_name }}</small>
                            </div>
                            <span class="badge {{ $approval->status === 'approved' ? 'bg-success' : ($approval->status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                {{ $approval->status_label }}
                            </span>
                        </div>
                        <hr>
                        <small class="text-muted d-block">Status Notifikasi</small>
                        <div>{{ $approval->seen_at ? 'Sudah dibuka pada ' . $approval->seen_at->format('d-m-Y H:i') : 'Belum dibuka' }}</div>
                        <small class="text-muted d-block mt-2">Catatan Approval</small>
                        <div>{{ $approval->note ?: '-' }}</div>
                        <small class="text-muted d-block mt-2">Tanggal & Waktu Approval</small>
                        <div>{{ $approval->acted_at ? $approval->acted_at->format('d-m-Y H:i') : '-' }}</div>
                        <small class="text-muted d-block mt-2">Nama User</small>
                        <div>{{ $approval->actor?->name ?? '-' }}</div>
                    </div>
                    @endforeach

                    <div class="border rounded p-3">
                        <strong>Realisasi Admin GA</strong><br>
                        <small class="text-muted">Notifikasi distribusi dan status ketersediaan barang</small>
                        <hr>
                        <small class="text-muted d-block">Notifikasi Dibuka</small>
                        <div>{{ $itemRequest->ga_seen_at ? $itemRequest->ga_seen_at->format('d-m-Y H:i') : 'Belum dibuka Admin GA' }}</div>
                        <small class="text-muted d-block mt-2">Status Realisasi</small>
                        <div>{{ $itemRequest->realization_label }}</div>
                        <small class="text-muted d-block mt-2">Catatan GA</small>
                        <div>{{ $itemRequest->realization_note ?: '-' }}</div>
                        <small class="text-muted d-block mt-2">Tanggal & Waktu</small>
                        <div>{{ $itemRequest->realized_at ? $itemRequest->realized_at->format('d-m-Y H:i') : '-' }}</div>
                        <small class="text-muted d-block mt-2">Stok Terpotong</small>
                        <div>{{ $itemRequest->stock_deducted_at ? $itemRequest->stock_deducted_at->format('d-m-Y H:i') : '-' }}</div>
                        <small class="text-muted d-block mt-2">User</small>
                        <div>{{ $itemRequest->realizer?->name ?? '-' }}</div>
                    </div>
                </div>
            </div>

            @if($canApprove && $activeApproval)
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Approval {{ $activeApproval->role_name }}</h5>
                    <form action="{{ route('item-requests.approvals.action', [$itemRequest, $activeApproval]) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Keputusan</label>
                            <select name="action" class="form-select" required>
                                <option value="">Pilih keputusan</option>
                                <option value="approve">Approve</option>
                                <option value="pending">Pending</option>
                                <option value="reject">Reject</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan Wajib</label>
                            <textarea name="note" class="form-control" rows="4" placeholder="Jelaskan alasan approval, pending, reject, atau kondisi stok/pembelian" required></textarea>
                        </div>
                        <button class="btn btn-primary w-100">Simpan Approval</button>
                    </form>
                </div>
            </div>
            @endif

            @if($canRealize)
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Realisasi Admin GA</h5>
                    <form action="{{ route('item-requests.realize', $itemRequest) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Status Realisasi</label>
                            <select name="realization_status" class="form-select" required>
                                <option value="">Pilih status realisasi</option>
                                <option value="ready_for_distribution">Barang siap didistribusikan</option>
                                <option value="distributed">Barang sudah diberikan</option>
                                <option value="purchase_required">Perlu pembelian terlebih dahulu</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pemetaan Barang ke On Stock</label>
                            <div class="small text-muted mb-2">Pilih stok hanya untuk item dengan jenis pemenuhan `Distribusi Stok`. Saat status `distributed`, stok di menu On Stock akan otomatis berkurang. Item `Permintaan Pembelian` tidak wajib dipetakan ke stok.</div>
                            @foreach($itemRequest->items as $itemIndex => $item)
                            <div class="border rounded p-2 mb-2">
                                <div class="fw-semibold">{{ $item->item_name }} ({{ rtrim(rtrim(number_format((float) $item->qty, 2, '.', ''), '0'), '.') }} {{ $item->unit }})</div>
                                <div class="small text-muted">Jenis pemenuhan: {{ $item->procurement_type_label }}</div>
                                <input type="hidden" name="items[{{ $itemIndex }}][id]" value="{{ $item->id }}">
                                @if(($item->procurement_type ?? 'stock_distribution') === 'purchase_request')
                                <div class="alert alert-warning py-2 px-3 mt-2 mb-0">
                                    Item ini diarahkan ke proses pembelian, jadi tidak perlu memilih barang dari On Stock.
                                </div>
                                @else
                                <select name="items[{{ $itemIndex }}][stock_id]" class="form-select mt-2">
                                    <option value="">Pilih barang dari On Stock</option>
                                    @foreach($stocks as $stock)
                                    <option value="{{ $stock->id }}" @selected((int) old('items.' . $itemIndex . '.stock_id', $item->stock_id) === (int) $stock->id)>
                                        {{ $stock->item_code ?: '-' }} - {{ $stock->item_name }} (stok: {{ $stock->qty }} {{ $stock->unit ?: '' }})
                                    </option>
                                    @endforeach
                                </select>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan Wajib</label>
                            <textarea name="note" class="form-control" rows="4" placeholder="Contoh: barang tersedia di stock, menunggu serah terima, atau belum ada stok sehingga perlu pembelian" required></textarea>
                        </div>
                        <button class="btn btn-success w-100">Simpan Realisasi</button>
                    </form>
                </div>
            </div>
            @endif

            @if($canExpire)
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Ubah ke Expired</h5>
                    <form action="{{ route('item-requests.expire', $itemRequest) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Jelaskan kenapa permintaan ini sudah tidak berlaku / expired" required></textarea>
                        </div>
                        <button class="btn btn-outline-secondary w-100">Tandai Expired</button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
