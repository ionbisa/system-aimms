@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-3">Inbound</h4>
    <p class="text-muted mb-3">Menampilkan histori barang masuk untuk bulan {{ $currentMonth->translatedFormat('F Y') }} secara otomatis.</p>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('stock.inbound') }}" class="row g-2 mb-3">
        <div class="col-md-5">
            <input
                type="text"
                name="search"
                value="{{ $search ?? '' }}"
                class="form-control"
                placeholder="Cari berdasarkan nama barang"
            >
        </div>
        <div class="col-md-auto">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="{{ route('stock.inbound') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('stock.inbound.store') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-md-8">
                    <label class="form-label">Pilih Barang</label>
                    <select name="stock_id" class="form-select" required>
                        <option value="">Pilih barang</option>
                        @foreach($stocks as $stock)
                        <option value="{{ $stock->id }}">{{ $stock->item_code }} - {{ $stock->item_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Qty Masuk</label>
                    <input type="number" name="qty" class="form-control" min="1" required>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Simpan Barang Masuk</button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th>No</th>
                    <th>Nama Barang</th>
                    <th>Qty</th>
                    <th>Waktu Input</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inbounds as $index => $item)
                <tr>
                    <td class="text-center">{{ method_exists($inbounds, 'firstItem') ? $inbounds->firstItem() + $index : $index + 1 }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td class="text-center">{{ $item->qty }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($item->created_at)->format('d-m-Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">Data barang masuk bulan {{ $currentMonth->translatedFormat('F Y') }} tidak ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($inbounds, 'links'))
    <div class="d-flex justify-content-center mt-3">
        {{ $inbounds->links('pagination::simple-bootstrap-5') }}
    </div>
    @endif
</div>
@endsection
