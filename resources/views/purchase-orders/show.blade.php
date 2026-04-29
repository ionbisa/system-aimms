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
            <h4 class="mb-1">Detail Purchase Order</h4>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge {{ $purchaseOrder->status_badge_class }}">{{ $purchaseOrder->display_status }}</span>
                <span class="badge text-bg-light">{{ $purchaseOrder->current_step_label }}</span>
                <span class="badge text-bg-info">{{ $purchaseOrder->realization_label }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('purchase-orders.print', $purchaseOrder) }}" target="_blank" class="btn btn-danger">Cetak / Simpan PDF</a>
            <a href="{{ route('purchase-orders.index', ['month' => optional($purchaseOrder->transaction_date)->format('Y-m')]) }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Informasi Form</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Judul</small>
                            <strong>PERMINTAAN PEMBELIAN / PEMBAYARAN / PERBAIKAN / BBM</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Tanggal dan Waktu</small>
                            <strong>{{ optional($purchaseOrder->transaction_date)->format('d-m-Y') }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">No PO</small>
                            <strong>{{ $purchaseOrder->po_number }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Jenis Permintaan</small>
                            <strong>{{ $purchaseOrder->transaction_type }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Divisi</small>
                            <strong>{{ $purchaseOrder->division }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Kategori</small>
                            <strong>{{ $purchaseOrder->category ?: '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Vendor</small>
                            <strong>{{ $purchaseOrder->vendor ?: '-' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Dibuat Oleh</small>
                            <strong>{{ $purchaseOrder->requester?->name ?? '-' }}</strong><br>
                            <small class="text-muted">{{ $purchaseOrder->requested_role ?? '-' }}</small>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Total Estimasi</small>
                            <strong>Rp {{ number_format((float) $purchaseOrder->total_price, 0, ',', '.') }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Total Realisasi</small>
                            <strong>Rp {{ number_format((float) $purchaseOrder->effective_total_price, 0, ',', '.') }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Selisih Estimasi vs Realisasi</small>
                            <strong class="{{ $purchaseOrder->price_variance < 0 ? 'text-success' : ($purchaseOrder->price_variance > 0 ? 'text-danger' : '') }}">
                                {{ $purchaseOrder->price_variance > 0 ? '+' : '' }}Rp {{ number_format((float) $purchaseOrder->price_variance, 0, ',', '.') }}
                            </strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Lama Proses</small>
                            <strong>{{ optional($purchaseOrder->created_at)?->diffForHumans(now(), true) ?? '-' }}</strong>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Uraian</small>
                            <div>{{ $purchaseOrder->description ?: '-' }}</div>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Catatan Awal</small>
                            <div>{{ $purchaseOrder->initial_note ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Detail Tabel Permintaan</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>No</th>
                                    <th>Nama Barang / Jasa</th>
                                    <th>Qty</th>
                                    <th>Satuan</th>
                                    <th>Estimasi Harga Satuan</th>
                                    <th>Jumlah Harga</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseOrder->items as $item)
                                <tr>
                                    <td class="text-center">{{ $item->line_number }}</td>
                                    <td>{{ $item->item_name }}</td>
                                    <td class="text-center">{{ rtrim(rtrim(number_format((float) $item->qty, 2, '.', ''), '0'), '.') }}</td>
                                    <td class="text-center">{{ $item->unit }}</td>
                                    <td class="text-end">Rp {{ number_format((float) $item->estimated_unit_price, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format((float) $item->estimated_total_price, 0, ',', '.') }}</td>
                                    <td>{{ $item->description ?: '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="text-end fw-semibold">Total Estimasi</td>
                                    <td class="text-end fw-bold">Rp {{ number_format((float) $purchaseOrder->total_price, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end fw-semibold">Total Realisasi</td>
                                    <td class="text-end fw-bold">Rp {{ number_format((float) $purchaseOrder->effective_total_price, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end fw-semibold">Selisih</td>
                                    <td class="text-end fw-bold {{ $purchaseOrder->price_variance < 0 ? 'text-success' : ($purchaseOrder->price_variance > 0 ? 'text-danger' : '') }}">
                                        {{ $purchaseOrder->price_variance > 0 ? '+' : '' }}Rp {{ number_format((float) $purchaseOrder->price_variance, 0, ',', '.') }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Riwayat & Catatan</h5>
                        <small class="text-muted">Catatan antar user dan histori approval/realisasi tercatat di sini.</small>
                    </div>

                    @if(($showGaCompletionHint ?? false) && $purchaseOrder->current_step === 'waiting_ga_completion')
                    <div class="alert alert-info">
                        Admin GA dapat menambahkan catatan proses di sini sebelum mengunggah bukti nota dan menandai Purchase Order sebagai <strong>Done</strong>.
                    </div>
                    @endif

                    <form action="{{ route('purchase-orders.comment', $purchaseOrder) }}" method="POST" class="row g-2 mb-3">
                        @csrf
                        <div class="col-md-10">
                            <input type="text" name="note" class="form-control" placeholder="Tambahkan catatan proses, vendor, pembayaran, atau tindak lanjut" required>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100">Kirim Catatan</button>
                        </div>
                    </form>

                    <div class="list-group">
                        @forelse($purchaseOrder->notes as $note)
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
                        <div class="list-group-item text-muted">Belum ada catatan pada Purchase Order ini.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Jalur Approval</h5>
                    @foreach($purchaseOrder->approvals as $approval)
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
                        <strong>Realisasi Manager Finance</strong><br>
                        <small class="text-muted">Status keputusan finance, kesiapan dana, dan tindak lanjut ke Admin GA</small>
                        <hr>
                        <small class="text-muted d-block">Notifikasi Dibuka</small>
                        <div>{{ $purchaseOrder->finance_seen_at ? $purchaseOrder->finance_seen_at->format('d-m-Y H:i') : 'Belum dibuka Manager Finance' }}</div>
                        <small class="text-muted d-block mt-2">Status Realisasi</small>
                        <div>{{ $purchaseOrder->realization_label }}</div>
                        <small class="text-muted d-block mt-2">Catatan Finance</small>
                        <div>{{ $purchaseOrder->realization_note ?: '-' }}</div>
                        <small class="text-muted d-block mt-2">Tanggal & Waktu</small>
                        <div>{{ $purchaseOrder->realized_at ? $purchaseOrder->realized_at->format('d-m-Y H:i') : '-' }}</div>
                        <small class="text-muted d-block mt-2">User</small>
                        <div>{{ $purchaseOrder->realizer?->name ?? '-' }}</div>
                    </div>

                    <div class="border rounded p-3 mt-3">
                        <strong>Catatan dan Bukti Nota Admin GA</strong><br>
                        <small class="text-muted">Catatan akhir Admin GA, bukti nota pembelanjaan atau pembayaran, dan penutupan proses PO</small>
                        <hr>
                        <small class="text-muted d-block">Notifikasi Dibuka</small>
                        <div>{{ $purchaseOrder->ga_seen_at ? $purchaseOrder->ga_seen_at->format('d-m-Y H:i') : 'Belum dibuka Admin GA' }}</div>
                        <small class="text-muted d-block mt-2">Catatan Admin GA</small>
                        <div>{{ $purchaseOrder->ga_completion_note ?: '-' }}</div>
                        <small class="text-muted d-block mt-2">Nominal Real Nota</small>
                        <div>Rp {{ number_format((float) $purchaseOrder->effective_total_price, 0, ',', '.') }}</div>
                        <small class="text-muted d-block mt-2">Bukti Nota</small>
                        @if($purchaseOrder->receipt_file_url)
                        <div><a href="{{ $purchaseOrder->receipt_file_url }}" target="_blank">Lihat bukti nota</a></div>
                        @else
                        <div>-</div>
                        @endif
                        <small class="text-muted d-block mt-2">Tanggal & Waktu Selesai</small>
                        <div>{{ $purchaseOrder->completed_at ? $purchaseOrder->completed_at->format('d-m-Y H:i') : '-' }}</div>
                        <small class="text-muted d-block mt-2">User</small>
                        <div>{{ $purchaseOrder->completer?->name ?? '-' }}</div>
                    </div>
                </div>
            </div>

            @if($canApprove && $activeApproval)
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Approval {{ $activeApproval->role_name }}</h5>
                    <form action="{{ route('purchase-orders.approvals.action', [$purchaseOrder, $activeApproval]) }}" method="POST">
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
                            <textarea name="note" class="form-control" rows="4" placeholder="Jelaskan alasan approve, pending, atau reject" required></textarea>
                        </div>
                        <button class="btn btn-primary w-100">Simpan Approval</button>
                    </form>
                </div>
            </div>
            @endif

            @if($canRealize)
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Realisasi Manager Finance</h5>
                    <form action="{{ route('purchase-orders.realize', $purchaseOrder) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Keputusan Manager Finance</label>
                            <select name="finance_action" id="financeActionSelect" class="form-select" required>
                                <option value="">Pilih keputusan</option>
                                <option value="approve">Approve</option>
                                <option value="pending">Pending</option>
                                <option value="reject">Reject</option>
                            </select>
                        </div>
                        <div class="mb-3" id="financeRealizationStatusWrapper">
                            <label class="form-label">Status Pencairan Dana</label>
                            <select name="realization_status" id="financeRealizationStatusSelect" class="form-select">
                                <option value="">Pilih status pencairan dana</option>
                                <option value="fund_ready">Uang siap dikeluarkan</option>
                                <option value="fund_disbursed">Uang sudah diberikan</option>
                            </select>
                            <small class="text-muted">Wajib dipilih jika keputusan Finance adalah approve.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan Wajib</label>
                            <textarea name="note" class="form-control" rows="4" placeholder="Jelaskan alasan approve, pending, reject, atau status pencairan dana" required></textarea>
                        </div>
                        <button class="btn btn-success w-100">Simpan Realisasi</button>
                    </form>
                </div>
            </div>
            @endif

            @if($canComplete)
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Selesaikan PO</h5>
                    <form action="{{ route('purchase-orders.complete', $purchaseOrder) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <input type="text" class="form-control" value="Done" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nominal Real Nota</label>
                            <input type="number" name="actual_total_price" class="form-control" min="0" step="1" value="{{ old('actual_total_price', round((float) $purchaseOrder->total_price)) }}" required>
                            <small class="text-muted">Isi sesuai nominal yang benar-benar dibayar pada nota. Boleh berbeda dari estimasi PO.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload Bukti Nota</label>
                            <input type="file" name="receipt_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                            <small class="text-muted">Format: JPG, PNG, atau PDF. Maksimal 5 MB.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan Admin GA</label>
                            <textarea name="note" class="form-control" rows="4" placeholder="Isi catatan pembelanjaan, pembayaran, atau penutupan Purchase Order" required></textarea>
                        </div>
                        <button class="btn btn-primary w-100">Simpan dan Tandai Done</button>
                    </form>
                </div>
            </div>
            @endif

            @if($canExpire)
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Kembalikan ke Pending</h5>
                    <form action="{{ route('purchase-orders.expire', $purchaseOrder) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Jelaskan kenapa Purchase Order ini perlu dikembalikan ke pending untuk follow up ulang" required></textarea>
                        </div>
                        <button class="btn btn-outline-warning w-100">Kembalikan ke Pending</button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const financeActionSelect = document.getElementById('financeActionSelect');
        const realizationWrapper = document.getElementById('financeRealizationStatusWrapper');
        const realizationSelect = document.getElementById('financeRealizationStatusSelect');

        if (!financeActionSelect || !realizationWrapper || !realizationSelect) {
            return;
        }

        const syncFinanceFields = () => {
            const requiresRealizationStatus = financeActionSelect.value === 'approve';

            realizationWrapper.style.display = requiresRealizationStatus ? '' : 'none';
            realizationSelect.required = requiresRealizationStatus;

            if (!requiresRealizationStatus) {
                realizationSelect.value = '';
            }
        };

        financeActionSelect.addEventListener('change', syncFinanceFields);
        syncFinanceFields();
    });
</script>
@endsection
