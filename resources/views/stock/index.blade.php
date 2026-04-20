@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">On Stock</h4>

        @role('Master Admin')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStockItemModal">
            Tambah Barang Baru
        </button>
        @endrole
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger">
        <strong>Data barang belum berhasil disimpan:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="GET" action="{{ route('stock.index') }}" class="row g-2 mb-3">
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
            <a href="{{ route('stock.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th>No</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Foto Barang</th>
                    <th>Lokasi</th>
                    <th>Kondisi Qty</th>
                    <th>Qty Barang</th>
                    <th>Satuan</th>
                    @role('Master Admin')
                    <th>Aksi</th>
                    @endrole
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $index => $stock)
                <tr>
                    <td class="text-center">{{ method_exists($stocks, 'firstItem') ? $stocks->firstItem() + $index : $index + 1 }}</td>
                    <td class="text-center">{{ $stock->item_code ?: '-' }}</td>
                    <td>{{ $stock->item_name ?: '-' }}</td>
                    <td class="text-center">
                        @if($stock->photo)
                        <img
                            src="{{ asset('storage/' . $stock->photo) }}"
                            alt="{{ $stock->item_name }}"
                            width="60"
                            class="rounded"
                            style="cursor:pointer"
                            data-bs-toggle="modal"
                            data-bs-target="#stockItem{{ $stock->id }}"
                        >
                        @else
                        <span class="text-muted small">Tidak ada</span>
                        @endif
                    </td>
                    <td>{{ $stock->location ?: '-' }}</td>
                    <td class="text-center">
                        @if($stock->qty <= 5)
                        <span class="badge bg-danger">Kurang</span>
                        @else
                        <span class="badge bg-success">Aman</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($stock->qty <= 5)
                        <span class="badge bg-danger">{{ $stock->qty }}</span>
                        @else
                        <span class="badge bg-success">{{ $stock->qty }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $stock->unit ?: '-' }}</td>
                    @role('Master Admin')
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editStockItem{{ $stock->id }}">
                            Edit
                        </button>
                        <form action="{{ route('stock.items.destroy', $stock->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus barang ini?')">
                                Hapus
                            </button>
                        </form>
                    </td>
                    @endrole
                </tr>

                <div class="modal fade" id="stockItem{{ $stock->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ $stock->item_name ?: 'Detail Barang' }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                @if($stock->photo)
                                <img src="{{ asset('storage/' . $stock->photo) }}" alt="{{ $stock->item_name }}" class="img-fluid rounded mb-3">
                                @endif

                                <div class="row g-3">
                                    <div class="col-md-6"><strong>Kode Barang:</strong> {{ $stock->item_code ?: '-' }}</div>
                                    <div class="col-md-6"><strong>Nama Barang:</strong> {{ $stock->item_name ?: '-' }}</div>
                                    <div class="col-md-6"><strong>Lokasi:</strong> {{ $stock->location ?: '-' }}</div>
                                    <div class="col-md-6"><strong>Satuan:</strong> {{ $stock->unit ?: '-' }}</div>
                                    <div class="col-md-6"><strong>Qty:</strong> {{ $stock->qty }}</div>
                                    <div class="col-md-6"><strong>Status:</strong> {{ $stock->status ?: '-' }}</div>
                                    <div class="col-12">
                                        <strong>Spesifikasi:</strong><br>
                                        {{ $stock->specification ?: '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @role('Master Admin')
                <div class="modal fade" id="editStockItem{{ $stock->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <form action="{{ route('stock.items.update', $stock->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Barang</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Kode Barang</label>
                                            <input type="text" name="item_code" class="form-control" value="{{ $stock->item_code }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Nama Barang</label>
                                            <input type="text" name="item_name" class="form-control" value="{{ $stock->item_name }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Lokasi</label>
                                            <input type="text" name="location" class="form-control" value="{{ $stock->location }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Qty Barang</label>
                                            <input type="number" name="qty" class="form-control" min="0" value="{{ $stock->qty }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Satuan</label>
                                            <select name="unit" class="form-select" required>
                                                <option value="PCS" @selected($stock->unit === 'PCS')>Pcs</option>
                                                <option value="BOX" @selected($stock->unit === 'BO1X')>Box</option>
                                                <option value="Roll" @selected($stock->unit === 'Roll')>Roll</option>
                                                <option value="DUS" @selected($stock->unit === 'DUS')>Dus</option>
                                                <option value="Pack" @selected($stock->unit === 'Pack')>Pack</option>
                                                 <option value="Botol" @selected($stock->unit === 'Botol')>Botol</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Status</label>
                                            <input type="text" name="status" class="form-control" value="{{ $stock->status ?: 'active' }}" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Spesifikasi Barang</label>
                                            <textarea name="specification" class="form-control" rows="4">{{ $stock->specification }}</textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Foto Barang</label>
                                            <input type="file" name="photo" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Update Barang</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endrole
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted">Belum ada data stok.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($stocks, 'links'))
    <div class="d-flex justify-content-center mt-3">
        {{ $stocks->links('pagination::simple-bootstrap-5') }}
    </div>
    @endif
</div>

@role('Master Admin')
<div class="modal fade" id="createStockItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('stock.items.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Barang Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kode Barang</label>
                            <input type="text" name="item_code" class="form-control" value="{{ old('item_code') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Barang</label>
                            <input type="text" name="item_name" class="form-control" value="{{ old('item_name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Qty Awal</label>
                            <input type="number" name="qty" class="form-control" min="0" value="{{ old('qty') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Satuan</label>
                            <select name="unit" class="form-select" required>
                                <option value="PCS" @selected(old('unit') === 'PCS')>Pcs</option>
                                <option value="BOX" @selected(old('unit') === 'BOX')>Box</option>
                                <option value="Roll" @selected(old('unit') === 'Roll')>Roll</option>
                                <option value="DUS" @selected(old('unit') === 'DUS')>Dus</option>
                                <option value="Pack" @selected(old('unit') === 'Pack')>Pack</option>
                                <option value="Botol" @selected(old('unit') === 'Botol')>Botol</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <input type="text" name="status" class="form-control" value="{{ old('status', 'active') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Spesifikasi Barang</label>
                            <textarea name="specification" class="form-control" rows="4">{{ old('specification') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Foto Barang</label>
                            <input type="file" name="photo" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan Barang</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const createModalElement = document.getElementById('createStockItemModal');

        if (!createModalElement || typeof bootstrap === 'undefined') {
            return;
        }

        bootstrap.Modal.getOrCreateInstance(createModalElement).show();
    });
</script>
@endif
@endrole
@endsection
