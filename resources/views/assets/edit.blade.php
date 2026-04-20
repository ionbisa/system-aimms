@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Edit Asset</h4>

    <form action="{{ route('assets.update', $asset->id) }}" method="POST" enctype="multipart/form-data" class="row g-3">
        @csrf
        @method('PUT')

        <div class="col-md-6">
            <label class="form-label">Kode Asset</label>
            <input type="text" name="asset_code" value="{{ old('asset_code', $asset->asset_code) }}" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Nama Asset</label>
            <input type="text" name="name" value="{{ old('name', $asset->name) }}" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Lokasi</label>
            <input type="text" name="location" value="{{ old('location', $asset->location) }}" class="form-control">
        </div>

        <div class="col-md-6">
            <label class="form-label">Nomor</label>
            <input type="text" name="nopol" value="{{ old('nopol', $asset->nopol) }}" class="form-control">
        </div>

        <div class="col-md-6">
            <label class="form-label">Tipe</label>
            <select name="type" class="form-select" required>
                <option value="Delivery Cars" @selected(old('type', $asset->type) === 'Delivery Cars')>Delivery Cars</option>
                <option value="Personal Cars" @selected(old('type', $asset->type) === 'Personal Cars')>Personal Cars</option>
                <option value="Motorcycles" @selected(old('type', $asset->type) === 'Motorcycles')>Motorcycles</option>
                <option value="Office Assets" @selected(old('type', $asset->type) === 'Office Assets')>Company Assets</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
                <option value="active" @selected(old('status', $asset->status) === 'active')>Active</option>
                <option value="maintenance" @selected(old('status', $asset->status) === 'maintenance')>Maintenance</option>
                <option value="disposed" @selected(old('status', $asset->status) === 'disposed')>Disposed</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">PIC</label>
            <input type="text" name="pic" value="{{ old('pic', $asset->pic) }}" class="form-control">
        </div>

        <div class="col-12">
            <label class="form-label">Spesifikasi</label>
            <textarea name="specification" class="form-control" rows="4">{{ old('specification', $asset->specification) }}</textarea>
        </div>

        <div class="col-12">
            <label class="form-label">Foto</label>
            <input type="file" name="photo" class="form-control">
        </div>

        @if($asset->photo)
        <div class="col-12">
            <img src="{{ asset('storage/' . $asset->photo) }}" alt="{{ $asset->name }}" class="img-thumbnail" style="max-height: 180px;">
        </div>
        @endif

        <div class="col-12">
            <button class="btn btn-primary">Update</button>
            <a href="{{ route('assets.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </form>
</div>
@endsection
