@extends('layouts.app')

@section('content')
<div class="container">
    <style>
        .asset-page .asset-toolbar {
            gap: 1rem;
        }

        .asset-page .asset-table {
            min-width: 980px;
        }

        .asset-page .asset-thumb {
            width: 64px;
            height: 64px;
            object-fit: cover;
            cursor: pointer;
        }

        .asset-page .asset-thumb-placeholder {
            width: 64px;
            height: 64px;
        }

        .asset-page .asset-name {
            min-width: 240px;
        }

        .asset-page .asset-info {
            min-width: 280px;
        }

        .asset-page .asset-actions {
            min-width: 140px;
        }

        .asset-page .asset-group-summary-card {
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
        }

        .asset-page .asset-group-summary-label {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }

        .asset-page .asset-group-summary-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.1;
        }

        .asset-page .asset-group-summary-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .asset-page .asset-modal-dialog {
            max-width: 920px;
        }

        .asset-page .asset-modal-hero {
            background: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            min-height: 100%;
            padding: 1rem;
        }

        .asset-page .asset-modal-photo {
            width: 100%;
            max-height: 360px;
            object-fit: cover;
            border-radius: 0.9rem;
            border: 1px solid #dbe3ea;
            background: #fff;
        }

        .asset-page .asset-modal-placeholder {
            min-height: 280px;
            border-radius: 0.9rem;
            border: 1px dashed #cbd5e1;
            background: rgba(255, 255, 255, 0.9);
        }

        .asset-page .asset-detail-card {
            height: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.9rem;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        }

        .asset-page .asset-detail-label {
            display: block;
            margin-bottom: 0.35rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #64748b;
        }

        .asset-page .asset-detail-value {
            color: #0f172a;
            word-break: break-word;
        }

        .asset-page .asset-modal-specification {
            white-space: pre-line;
            word-break: break-word;
        }

        .asset-page .asset-placement-table th,
        .asset-page .asset-placement-table td {
            vertical-align: middle;
            white-space: nowrap;
        }
    </style>

    @php
        $statusStyles = [
            'active' => 'success',
            'maintenance' => 'warning',
            'disposed' => 'secondary',
        ];

        $isCompanyAssetView = $selectedCategory === 'company';
    @endphp

    <div class="asset-page">
    <div class="d-flex justify-content-between align-items-center flex-wrap asset-toolbar mb-3">
        <div>
            <h4 class="mb-0">{{ $pageTitle ?? 'Asset Management' }}</h4>
            @if(!empty($selectedCategory))
            <small class="text-muted">Filter menu: {{ ucfirst($selectedCategory) }} • {{ $assets->count() }} data pada halaman ini</small>
            @else
            <small class="text-muted">{{ $assets->count() }} data pada halaman ini</small>
            @endif
        </div>

        @role('Master Admin')
        <a href="{{ route('assets.create') }}" class="btn btn-primary">Tambah Asset</a>
        @endrole
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm" role="alert">
        {{ session('success') }}
    </div>
    @endif

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
                placeholder="Cari berdasarkan nama asset"
            >
        </div>
        <div class="col-md-auto">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="{{ route('assets.index', array_filter(['category' => $selectedCategory])) }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-1">Daftar Barang</h5>
            <small class="text-muted">Setiap baris menunjukkan unit barang per lokasi atau PIC penempatan. Klik foto atau detail untuk melihat informasi lengkap dan ringkasan barang sejenis.</small>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 asset-table">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th>Foto</th>
                            <th class="text-start">Asset</th>
                            <th class="text-start">Informasi</th>
                            <th class="text-start">PIC</th>
                            <th>Status</th>
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
                                    class="rounded border asset-thumb"
                                    data-bs-toggle="modal"
                                    data-bs-target="#asset{{ $asset->id }}"
                                >
                                @else
                                <div class="asset-thumb-placeholder rounded border bg-light d-inline-flex align-items-center justify-content-center text-muted">
                                    <i class="bi bi-image fs-4"></i>
                                </div>
                                @endif
                            </td>
                            <td class="asset-name">
                                <div class="fw-semibold">{{ $asset->name }}</div>
                                <div class="small text-muted font-monospace">{{ $asset->asset_code }}</div>
                                <div class="small text-muted mt-2">Klik foto atau tombol detail untuk melihat spesifikasi.</div>
                            </td>
                            <td class="asset-info">
                                <div><span class="text-muted small">Lokasi:</span> {{ $asset->location ?: '-' }}</div>
                                <div><span class="text-muted small">Nomor:</span> {{ $asset->nopol ?: '-' }}</div>
                                <div><span class="text-muted small">Tipe:</span> {{ $asset->type }}</div>
                            </td>
                            <td>{{ $asset->pic ?: '-' }}</td>
                            <td class="text-center">
                                <span class="badge text-bg-{{ $statusStyles[$asset->status] ?? 'light' }} text-capitalize">
                                    {{ $asset->status }}
                                </span>
                            </td>
                            <td class="asset-actions">
                                <div class="d-grid gap-2">
                                    <button
                                        class="btn btn-outline-info btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#asset{{ $asset->id }}"
                                    >
                                        Detail
                                    </button>

                                    @role('Master Admin|Admin GA')
                                    <a href="{{ route('assets.edit', $asset->id) }}" class="btn btn-outline-warning btn-sm">Edit</a>
                                    @endrole

                                    @role('Master Admin|Admin GA')
                                    <form action="{{ route('assets.destroy', $asset->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Yakin hapus?')">
                                            Hapus
                                        </button>
                                    </form>
                                    @endrole
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada data asset.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if(method_exists($assets, 'links'))
    <div class="d-flex justify-content-center mt-3">
        {{ $assets->links('pagination::simple-bootstrap-5') }}
    </div>
    @endif

    @foreach($assets as $asset)
    @php
        $assetSummary = null;

        if ($isCompanyAssetView) {
            $summaryKey = \App\Models\Asset::groupedSummaryKey($asset->name, $asset->type);
            $assetSummary = $assetSummaryMap->get($summaryKey);
        }
    @endphp
    <div class="modal fade" id="asset{{ $asset->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable asset-modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <div class="w-100 d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="small text-muted text-uppercase fw-semibold">Detail Barang</div>
                            <h5 class="modal-title mb-1">{{ $asset->name }}</h5>
                            <div class="small text-muted font-monospace">{{ $asset->asset_code }}</div>
                        </div>
                        <span class="badge text-bg-{{ $statusStyles[$asset->status] ?? 'light' }} text-capitalize mt-1">
                            {{ $asset->status }}
                        </span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-4 align-items-stretch">
                        <div class="col-lg-5">
                            <div class="asset-modal-hero h-100">
                                @if($asset->photo)
                                <img
                                    src="{{ asset('storage/' . $asset->photo) }}"
                                    alt="{{ $asset->name }}"
                                    class="asset-modal-photo"
                                >
                                @else
                                <div class="asset-modal-placeholder d-flex flex-column align-items-center justify-content-center text-muted">
                                    <i class="bi bi-image fs-1 mb-2"></i>
                                    <div class="fw-semibold">Foto belum tersedia</div>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="asset-detail-card">
                                        <span class="asset-detail-label">Lokasi</span>
                                        <div class="asset-detail-value">{{ $asset->location ?: '-' }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="asset-detail-card">
                                        <span class="asset-detail-label">Nomor</span>
                                        <div class="asset-detail-value">{{ $asset->nopol ?: '-' }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="asset-detail-card">
                                        <span class="asset-detail-label">Tipe</span>
                                        <div class="asset-detail-value">{{ $asset->type }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="asset-detail-card">
                                        <span class="asset-detail-label">PIC</span>
                                        <div class="asset-detail-value">{{ $asset->pic ?: '-' }}</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="asset-detail-card">
                                        <span class="asset-detail-label">Spesifikasi</span>
                                        <div class="asset-detail-value asset-modal-specification">
                                            {{ $asset->specification ?: 'Belum ada spesifikasi untuk barang ini.' }}
                                        </div>
                                    </div>
                                </div>

                                @if($assetSummary)
                                <div class="col-12">
                                    <div class="asset-detail-card">
                                        <span class="asset-detail-label">Ringkasan Barang Sejenis</span>
                                        <div class="small text-muted mb-3">Ringkasan ini menghitung seluruh Company Assets dengan nama dan tipe yang sama.</div>
                                        <div class="row g-3">
                                            <div class="col-sm-6 col-xl-3">
                                                <div class="asset-group-summary-card p-3 h-100">
                                                    <div class="asset-group-summary-label">Total Unit</div>
                                                    <div class="asset-group-summary-value">{{ $assetSummary['total_qty'] }}</div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 col-xl-3">
                                                <div class="asset-group-summary-card p-3 h-100">
                                                    <div class="asset-group-summary-label">Active</div>
                                                    <div class="asset-group-summary-value text-success">{{ $assetSummary['active_qty'] }}</div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 col-xl-3">
                                                <div class="asset-group-summary-card p-3 h-100">
                                                    <div class="asset-group-summary-label">Maintenance</div>
                                                    <div class="asset-group-summary-value text-warning">{{ $assetSummary['maintenance_qty'] }}</div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 col-xl-3">
                                                <div class="asset-group-summary-card p-3 h-100">
                                                    <div class="asset-group-summary-label">Disposed</div>
                                                    <div class="asset-group-summary-value text-secondary">{{ $assetSummary['disposed_qty'] }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="asset-detail-card">
                                        <span class="asset-detail-label">Lokasi Penempatan Barang Sejenis</span>
                                        @if($assetSummary['locations']->isNotEmpty())
                                        <div class="asset-group-summary-list">
                                            @foreach($assetSummary['locations'] as $location)
                                            <span class="badge rounded-pill text-bg-light border">{{ $location }}</span>
                                            @endforeach
                                        </div>
                                        @else
                                        <div class="text-muted">Belum ada lokasi penempatan.</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="asset-detail-card">
                                        <span class="asset-detail-label">PIC Barang Sejenis</span>
                                        @if($assetSummary['pics']->isNotEmpty())
                                        <div class="asset-group-summary-list">
                                            @foreach($assetSummary['pics'] as $pic)
                                            <span class="badge rounded-pill text-bg-light border">{{ $pic }}</span>
                                            @endforeach
                                        </div>
                                        @else
                                        <div class="text-muted">Belum ada PIC.</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="asset-detail-card">
                                        <span class="asset-detail-label">Daftar Penempatan Barang Sejenis</span>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle asset-placement-table mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Kode Asset</th>
                                                        <th>Lokasi</th>
                                                        <th>PIC</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($assetSummary['placements'] as $placement)
                                                    <tr>
                                                        <td class="font-monospace">{{ $placement['asset_code'] }}</td>
                                                        <td>{{ $placement['location'] }}</td>
                                                        <td>{{ $placement['pic'] }}</td>
                                                        <td>
                                                            <span class="badge text-bg-{{ $statusStyles[$placement['status']] ?? 'light' }} text-capitalize">
                                                                {{ $placement['status'] }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
</div>
@endsection
