@extends('layouts.app')

@section('content')
<div class="container">
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
        <div>
            <h4 class="mb-1">Permintaan Barang</h4>
            <small class="text-muted">Pantau pengajuan, approval digital, notifikasi status, dan realisasi distribusi barang dalam satu alur.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('item-requests.export', request()->query()) }}" class="btn btn-success">Download Excel (.csv)</a>
            <a href="{{ route('item-requests.print-monthly', request()->query()) }}" target="_blank" class="btn btn-danger">Print Laporan Bulanan</a>
            @if($canCreate)
            <a href="{{ route('item-requests.create') }}" class="btn btn-primary">Buat Permintaan</a>
            @endif
        </div>
    </div>

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
            <div class="card shadow-sm h-100 border-secondary">
                <div class="card-body">
                    <small class="text-muted d-block">Expired</small>
                    <h3 class="mb-0 text-secondary">{{ $summary->expired_total ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('item-requests.index') }}" class="row g-2 mb-3">
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
                <option value="expired" @selected($status === 'expired')>Expired</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Cari</label>
            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="No permintaan, divisi, pemohon, nama barang">
        </div>
        <div class="col-md-auto align-self-end">
            <button class="btn btn-primary">Filter</button>
            <a href="{{ route('item-requests.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th>No</th>
                    <th>No Permintaan</th>
                    <th>Tanggal</th>
                    <th>Divisi</th>
                    <th>Dibuat Oleh</th>
                    <th>Ringkasan Barang</th>
                    <th>Status</th>
                    <th>Alur Proses</th>
                    <th>Stok Terpotong</th>
                    <th>Notifikasi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($itemRequests as $index => $itemRequest)
                @php
                    $activeApproval = $itemRequest->approvals->firstWhere('status', 'pending');
                    $notificationStatus = 'Sudah dibaca';

                    if ($itemRequest->current_step === 'waiting_ga_realization') {
                        $notificationStatus = $itemRequest->ga_seen_at ? 'Sudah dibaca Admin GA' : 'Belum dibuka Admin GA';
                    } elseif ($activeApproval) {
                        $notificationStatus = $activeApproval->seen_at ? 'Sudah dibaca ' . $activeApproval->role_name : 'Belum dibuka ' . $activeApproval->role_name;
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ method_exists($itemRequests, 'firstItem') ? $itemRequests->firstItem() + $index : $index + 1 }}</td>
                    <td>
                        <strong>{{ $itemRequest->request_number }}</strong><br>
                        <small class="text-muted">{{ $itemRequest->requested_role ?? '-' }}</small>
                    </td>
                    <td class="text-center">{{ optional($itemRequest->requested_at)->format('d-m-Y H:i') }}</td>
                    <td>{{ $itemRequest->division }}</td>
                    <td>{{ $itemRequest->requester?->name ?? '-' }}</td>
                    <td>
                        @foreach($itemRequest->items->take(2) as $item)
                        <div>{{ $item->item_name }} ({{ rtrim(rtrim(number_format((float) $item->qty, 2, '.', ''), '0'), '.') }} {{ $item->unit }})</div>
                        @endforeach
                        @if($itemRequest->items->count() > 2)
                        <small class="text-muted">+{{ $itemRequest->items->count() - 2 }} item lainnya</small>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $itemRequest->status_badge_class }}">{{ $itemRequest->status_label }}</span>
                    </td>
                    <td>
                        <strong>{{ $itemRequest->current_step_label }}</strong><br>
                        <small class="text-muted">{{ $itemRequest->realization_label }}</small>
                    </td>
                    <td class="text-center">{{ $itemRequest->stock_deducted_at ? $itemRequest->stock_deducted_at->format('d-m-Y H:i') : '-' }}</td>
                    <td>{{ $notificationStatus }}</td>
                    <td class="text-center">
                        <a href="{{ route('item-requests.show', $itemRequest) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center text-muted">Belum ada data permintaan barang pada filter ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($itemRequests, 'links'))
    <div class="d-flex justify-content-center mt-3">
        {{ $itemRequests->links('pagination::simple-bootstrap-5') }}
    </div>
    @endif
</div>
@endsection
