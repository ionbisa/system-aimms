@extends('layouts.app')

@section('content')
<div class="container">
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(!($schemaReady ?? true))
    <div class="alert alert-warning">
        Modul Purchase Order versi workflow belum siap karena migrasi terbaru belum dijalankan.
    </div>
    @endif

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
        <div>
            <h4 class="mb-1">Purchase Order (PO)</h4>
            <small class="text-muted">Pantau permintaan pembelian barang, pembayaran jasa, perbaikan barang, pembelian BBM, approval digital, realisasi Finance, dan penyelesaian Admin GA.</small>
        </div>
        <div class="d-flex gap-2">
            @if($schemaReady ?? false)
            <a href="{{ route('purchase-orders.export', request()->query()) }}" class="btn btn-success">Download Excel (.csv)</a>
            <a href="{{ route('purchase-orders.print-monthly', request()->query()) }}" target="_blank" class="btn btn-danger">Print Laporan</a>
            @endif
            @if($canCreate && ($schemaReady ?? false))
            <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">Buat PO</a>
            @endif
        </div>
    </div>

    @if($schemaReady ?? false)
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted d-block">Total Bulan Ini</small>
                    <h3 class="mb-0">{{ $summary->total ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100 border-warning">
                <div class="card-body">
                    <small class="text-muted d-block">Pending</small>
                    <h3 class="mb-0 text-warning">{{ $summary->pending_total ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm h-100 border-success">
                <div class="card-body">
                    <small class="text-muted d-block">Approved</small>
                    <h3 class="mb-0 text-success">{{ $summary->approved_total ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm h-100 border-danger">
                <div class="card-body">
                    <small class="text-muted d-block">Rejected</small>
                    <h3 class="mb-0 text-danger">{{ $summary->rejected_total ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm h-100 border-primary">
                <div class="card-body">
                    <small class="text-muted d-block">Done</small>
                    <h3 class="mb-0 text-primary">{{ $summary->done_total ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <small class="text-muted d-block">Pengeluaran Biaya Bulan Ini</small>
            <h4 class="mb-0">Rp {{ number_format((float) ($summary->monthly_total ?? 0), 0, ',', '.') }}</h4>
        </div>
    </div>
    @endif

    <form method="GET" action="{{ route('purchase-orders.index') }}" class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label">Bulan</label>
            <input type="month" name="month" value="{{ $selectedMonth }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">Semua Status</option>
                <option value="pending" @selected($status === 'pending')>Pending</option>
                <option value="approved" @selected($status === 'approved')>Approved</option>
                <option value="rejected" @selected($status === 'rejected')>Rejected</option>
                <option value="done" @selected($status === 'done')>Done</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Cari</label>
            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="No PO, jenis, divisi, kategori, vendor, uraian, nama item">
        </div>
        <div class="col-md-auto align-self-end">
            <button class="btn btn-primary">Filter</button>
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th>No</th>
                    <th>No PO</th>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Divisi</th>
                    <th>Kategori</th>
                    <th>Vendor</th>
                    <th>Dibuat Oleh</th>
                    <th>Ringkasan</th>
                    <th>Status</th>
                    <th>Alur Proses</th>
                    <th>Notifikasi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseOrders as $index => $purchaseOrder)
                @php
                    $activeApproval = $purchaseOrder->approvals->firstWhere('status', 'pending');
                    $notificationStatus = 'Sudah dibaca';
                    $hasActualPrice = !is_null($purchaseOrder->actual_total_price);
                    $variance = (float) $purchaseOrder->price_variance;
                    $varianceLabel = 'Estimasi';
                    $varianceClass = 'bg-secondary';

                    if ($purchaseOrder->current_step === 'waiting_finance_realization') {
                        $notificationStatus = $purchaseOrder->finance_seen_at ? 'Sudah dibaca Manager Finance' : 'Belum dibuka Manager Finance';
                    } elseif ($purchaseOrder->current_step === 'waiting_ga_completion') {
                        $notificationStatus = $purchaseOrder->ga_seen_at ? 'Sudah dibaca Admin GA' : 'Belum dibuka Admin GA';
                    } elseif ($activeApproval) {
                        $notificationStatus = $activeApproval->seen_at ? 'Sudah dibaca ' . $activeApproval->role_name : 'Belum dibuka ' . $activeApproval->role_name;
                    }

                    if ($hasActualPrice) {
                        if ($variance < 0) {
                            $varianceLabel = 'Hemat';
                            $varianceClass = 'bg-success';
                        } elseif ($variance > 0) {
                            $varianceLabel = 'Lebih besar';
                            $varianceClass = 'bg-danger';
                        } else {
                            $varianceLabel = 'Sesuai estimasi';
                            $varianceClass = 'bg-primary';
                        }
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ method_exists($purchaseOrders, 'firstItem') ? $purchaseOrders->firstItem() + $index : $index + 1 }}</td>
                    <td><strong>{{ $purchaseOrder->po_number }}</strong></td>
                    <td class="text-center">{{ optional($purchaseOrder->transaction_date)->format('d-m-Y') }}</td>
                    <td>{{ $purchaseOrder->transaction_type ?: '-' }}</td>
                    <td>{{ $purchaseOrder->division ?: '-' }}</td>
                    <td>{{ $purchaseOrder->category ?: '-' }}</td>
                    <td>{{ $purchaseOrder->vendor ?: '-' }}</td>
                    <td>{{ $purchaseOrder->requester?->name ?? '-' }}</td>
                    <td>
                        <div class="fw-semibold">{{ $purchaseOrder->description ?: '-' }}</div>
                        @foreach($purchaseOrder->items->take(2) as $item)
                        <div>{{ $item->item_name }} ({{ rtrim(rtrim(number_format((float) $item->qty, 2, '.', ''), '0'), '.') }} {{ $item->unit }})</div>
                        @endforeach
                        @if($purchaseOrder->items->count() > 2)
                        <small class="text-muted">+{{ $purchaseOrder->items->count() - 2 }} item lainnya</small>
                        @endif
                        <div class="mt-2">
                            <small class="text-muted d-block">Estimasi: Rp {{ number_format((float) $purchaseOrder->total_price, 0, ',', '.') }}</small>
                            <small class="text-muted d-block">Realisasi: Rp {{ number_format((float) $purchaseOrder->effective_total_price, 0, ',', '.') }}</small>
                            <span class="badge {{ $varianceClass }} mt-1">{{ $varianceLabel }}</span>
                            @if($hasActualPrice)
                            <small class="d-block {{ $variance < 0 ? 'text-success' : ($variance > 0 ? 'text-danger' : 'text-primary') }}">
                                Selisih: {{ $variance > 0 ? '+' : '' }}Rp {{ number_format($variance, 0, ',', '.') }}
                            </small>
                            @endif
                        </div>
                    </td>
                    <td class="text-center"><span class="badge {{ $purchaseOrder->status_badge_class }}">{{ $purchaseOrder->display_status }}</span></td>
                    <td>
                        <strong>{{ $purchaseOrder->current_step_label }}</strong><br>
                        <small class="text-muted">{{ $purchaseOrder->realization_label }}</small>
                    </td>
                    <td>{{ $notificationStatus }}</td>
                    <td class="text-center">
                        @if($schemaReady ?? false)
                        <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                        @else
                        <span class="text-muted small">Menunggu migrasi</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="13" class="text-center text-muted">Belum ada data Purchase Order pada filter ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($purchaseOrders, 'links'))
    <div class="d-flex justify-content-center mt-3">
        {{ $purchaseOrders->links('pagination::simple-bootstrap-5') }}
    </div>
    @endif
</div>
@endsection
