@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">{{ $pageTitle ?? 'Asset Management' }}</h4>
            @if(!empty($selectedCategory))
            <small class="text-muted">Filter menu: {{ ucfirst($selectedCategory) }}</small>
            @endif
        </div>

        @role('Master Admin')
        <a href="{{ route('assets.create') }}" class="btn btn-primary">Tambah Asset</a>
        @endrole
    </div>

    <form method="GET" action="{{ route('assets.index') }}" class="row g-2 mb-3">
        @if(!empty($selectedCategory))
        <input type="hidden" name="category" value="{{ $selectedCategory }}">
        @endif
        <div class="col-md-5">
            <input
                type="text"
                name="search"
                value="{{ $search ?? '' }}"
                class="form-control"
                placeholder="Cari berdasarkan nama"
            >
        </div>
        <div class="col-md-auto">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="{{ route('assets.index', array_filter(['category' => $selectedCategory])) }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th>Foto</th>
                    <th>Kode Asset</th>
                    <th>Nama</th>
                    <th>Lokasi</th>
                    <th>Nomor</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th>PIC</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assets as $asset)
                <tr>
                    <td class="text-center">
                        @if($asset->photo)
                        <img
                            src="{{ asset('storage/' . $asset->photo) }}"
                            alt="{{ $asset->name }}"
                            width="60"
                            class="rounded"
                            style="cursor:pointer"
                            data-bs-toggle="modal"
                            data-bs-target="#asset{{ $asset->id }}"
                        >
                        @else
                        <span class="text-muted small">Tidak ada</span>
                        @endif
                    </td>
                    <td>{{ $asset->asset_code }}</td>
                    <td>{{ $asset->name }}</td>
                    <td>{{ $asset->location ?: '-' }}</td>
                    <td>{{ $asset->nopol ?: '-' }}</td>
                    <td>{{ $asset->type }}</td>
                    <td class="text-capitalize">{{ $asset->status }}</td>
                    <td>{{ $asset->pic ?: '-' }}</td>
                    <td class="text-center">
                        <button
                            class="btn btn-info btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#asset{{ $asset->id }}"
                        >
                            Detail
                        </button>

                        @role('Master Admin|Admin GA')
                        <a href="{{ route('assets.edit', $asset->id) }}" class="btn btn-warning btn-sm">Edit</a>
                        @endrole

                        @role('Master Admin|Admin GA')
                        <form action="{{ route('assets.destroy', $asset->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus?')">
                                Hapus
                            </button>
                        </form>
                        @endrole
                    </td>
                </tr>

                <div class="modal fade" id="asset{{ $asset->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ $asset->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                @if($asset->photo)
                                <img src="{{ asset('storage/' . $asset->photo) }}" alt="{{ $asset->name }}" class="img-fluid rounded mb-3">
                                @endif

                                <div class="row g-3">
                                    <div class="col-md-6"><strong>Kode Asset :</strong> {{ $asset->asset_code }}</div>
                                    <div class="col-md-6"><strong>Nama :</strong> {{ $asset->name }}</div>
                                    <div class="col-md-6"><strong>Lokasi :</strong> {{ $asset->location ?: '-' }}</div>
                                    <div class="col-md-6"><strong>Nomor :</strong> {{ $asset->nopol ?: '-' }}</div>
                                    <div class="col-md-6"><strong>Tipe :</strong> {{ $asset->type }}</div>
                                    <div class="col-md-6"><strong>Status :</strong> {{ $asset->status }}</div>
                                    <div class="col-md-6"><strong>PIC :</strong> {{ $asset->pic ?: '-' }}</div>
                                    <div class="col-12">
                                        <strong>Spesifikasi :</strong><br>
                                        {{ $asset->specification ?: '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted">Belum ada data asset.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($assets, 'links'))
    <div class="d-flex justify-content-center mt-3">
        {{ $assets->links('pagination::simple-bootstrap-5') }}
    </div>
    @endif
</div>
@endsection
