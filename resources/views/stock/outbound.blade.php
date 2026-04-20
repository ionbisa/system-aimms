@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-3">Outbound</h4>
    <p class="text-muted mb-3">Menampilkan histori barang keluar untuk bulan {{ $currentMonth->translatedFormat('F Y') }} secara otomatis.</p>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('stock.outbound') }}" class="row g-2 mb-3">
        <div class="col-md-5">
            <input
                type="text"
                name="search"
                value="{{ $search ?? '' }}"
                class="form-control"
                placeholder="Cari berdasarkan nama barang atau keterangan"
            >
        </div>
        <div class="col-md-auto">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="{{ route('stock.outbound') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="alert alert-info mb-4">
        Menu <strong>Outbound</strong> sekarang hanya menampilkan histori barang keluar otomatis dari proses distribusi permintaan barang.
        Input manual outbound sudah dinonaktifkan agar stok tidak terpotong dua kali.
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th>No</th>
                    <th>Nama Barang</th>
                    <th>Qty</th>
                    <th>Satuan</th>
                    <th>Keterangan</th>
                    <th>Waktu Input</th>
                </tr>
            </thead>
            <tbody>
                @forelse($outbounds as $index => $item)
                <tr>
                    <td class="text-center">{{ method_exists($outbounds, 'firstItem') ? $outbounds->firstItem() + $index : $index + 1 }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td class="text-center">{{ $item->qty }}</td>
                    <td class="text-center">{{ isset($item->unit) && $item->unit !== '' ? $item->unit : '-' }}</td>
                    <td>{{ isset($item->description) && $item->description !== '' ? $item->description : '-' }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($item->created_at)->format('d-m-Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">Data barang keluar bulan {{ $currentMonth->translatedFormat('F Y') }} tidak ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($outbounds, 'links'))
    <div class="d-flex justify-content-center mt-3">
        {{ $outbounds->links('pagination::simple-bootstrap-5') }}
    </div>
    @endif
</div>

@endsection
