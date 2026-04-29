@extends('layouts.app')

@php($specificationMaxLength = \App\Models\Asset::SPECIFICATION_MAX_LENGTH)

@section('content')
<div class="container">
    <h4>Tambah Asset</h4>

    <form action="{{ route('assets.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
        @csrf

        <div class="col-md-6">
            <label class="form-label">Kode Asset</label>
            <input type="text" name="asset_code" value="{{ old('asset_code') }}" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Nama Asset</label>
            <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Lokasi</label>
            <input type="text" name="location" value="{{ old('location') }}" class="form-control">
        </div>

        <div class="col-md-6">
            <label class="form-label">Nomor</label>
            <input type="text" name="nopol" value="{{ old('nopol') }}" class="form-control">
        </div>

        <div class="col-md-6">
            <label class="form-label">Tipe</label>
            <select name="type" class="form-select" required>
                <option value="Delivery Cars" @selected(old('type') === 'Delivery Cars')>Delivery Cars</option>
                <option value="Personal Cars" @selected(old('type') === 'Personal Cars')>Personal Cars</option>
                <option value="Motorcycles" @selected(old('type') === 'Motorcycles')>Motorcycles</option>
                <option value="Office Assets" @selected(old('type') === 'Office Assets')>Company Assets</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
                <option value="active" @selected(old('status') === 'active')>Active</option>
                <option value="maintenance" @selected(old('status') === 'maintenance')>Maintenance</option>
                <option value="disposed" @selected(old('status') === 'disposed')>Disposed</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">PIC</label>
            <input type="text" name="pic" value="{{ old('pic') }}" class="form-control">
        </div>

        <div class="col-12">
            <label class="form-label">Spesifikasi</label>
            <textarea
                id="specification"
                name="specification"
                class="form-control @error('specification') is-invalid @enderror"
                rows="5"
                maxlength="{{ $specificationMaxLength }}"
                placeholder="Masukkan spesifikasi barang secara ringkas dan jelas"
            >{{ old('specification') }}</textarea>
            <div class="d-flex justify-content-between mt-1">
                <small class="text-muted">Maksimal {{ $specificationMaxLength }} karakter.</small>
                <small class="text-muted" id="specificationCounter">0/{{ $specificationMaxLength }} karakter</small>
            </div>
            @error('specification')
            <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label">Foto</label>
            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp">
        </div>

        <div class="col-12">
            <button class="btn btn-success">Simpan</button>
            <a href="{{ route('assets.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const textarea = document.getElementById('specification');
            const counter = document.getElementById('specificationCounter');

            if (!textarea || !counter) {
                return;
            }

            const updateCounter = () => {
                counter.textContent = `${textarea.value.length}/${textarea.maxLength} karakter`;
            };

            textarea.addEventListener('input', updateCounter);
            updateCounter();
        });
    </script>
</div>
@endsection
