@extends('layouts.app')

@php
    /** @var \App\Models\User|null $authUser */
    $authUser = \Illuminate\Support\Facades\Auth::user();
    $tableColumnCount = $authUser?->hasAnyRole(['Master Admin', 'Admin GA']) ? 14 : 13;
@endphp

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">ABP Sepatu Boots</h4>
            <small class="text-muted">Masa pakai sepatu dihitung otomatis selama 180 hari.</small>
        </div>

        @role('Master Admin|Admin GA')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEmployeeBootModal">
            Tambah Data Sepatu
        </button>
        @endrole
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger">
        <strong>Data belum berhasil disimpan:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="GET" action="{{ route('employee-boots.index') }}" class="row g-2 mb-3">
        <div class="col-md-4">
            <input
                type="text"
                name="search"
                value="{{ $search ?? '' }}"
                class="form-control"
                placeholder="Cari nama pegawai, NIK, atau departemen"
            >
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Semua Status</option>
                <option value="Aktif" @selected(($status ?? '') === 'Aktif')>Aktif</option>
                <option value="Habis" @selected(($status ?? '') === 'Habis')>Habis</option>
            </select>
        </div>
        <div class="col-md-auto">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="{{ route('employee-boots.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th>No</th>
                    <th>Tanggal Pengambilan</th>
                    <th>Tanggal Habis</th>
                    <th>Sisa Hari</th>
                    <th>Status</th>
                    <th>Nama Pegawai</th>
                    <th>NIK/Kode Pegawai</th>
                    <th>Departemen</th>
                    <th>Size Boot</th>
                    <th>Jumlah Diberikan</th>
                    <th>Kondisi</th>
                    <th>Keterangan</th>
                    <th>Foto</th>
                    @role('Master Admin|Admin GA')
                    <th>Aksi</th>
                    @endrole
                </tr>
            </thead>
            <tbody>
                @forelse($employeeBoots as $index => $employeeBoot)
                <tr>
                    <td class="text-center">{{ method_exists($employeeBoots, 'firstItem') ? $employeeBoots->firstItem() + $index : $index + 1 }}</td>
                    <td class="text-center">{{ optional($employeeBoot->return_date)->format('d-m-Y') ?? '-' }}</td>
                    <td class="text-center">{{ optional($employeeBoot->expiry_date)->format('d-m-Y') ?? '-' }}</td>
                    <td class="text-center">
                        @if($employeeBoot->status_label === 'Habis')
                        <span class="badge bg-danger">0 Hari</span>
                        @else
                        <span class="badge bg-warning text-dark">{{ $employeeBoot->remaining_days }} Hari</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $employeeBoot->status_label === 'Aktif' ? 'bg-success' : 'bg-danger' }}">
                            {{ $employeeBoot->status_label }}
                        </span>
                    </td>
                    <td>{{ $employeeBoot->employee_name }}</td>
                    <td>{{ $employeeBoot->employee_code }}</td>
                    <td>{{ $employeeBoot->department }}</td>
                    <td class="text-center">{{ $employeeBoot->boot_size }}</td>
                    <td class="text-center">{{ $employeeBoot->quantity_given }}</td>
                    <td>{{ $employeeBoot->condition }}</td>
                    <td>{{ $employeeBoot->notes }}</td>
                    <td class="text-center">
                        @if($employeeBoot->photo)
                        <img
                            src="{{ asset('storage/' . $employeeBoot->photo) }}"
                            alt="{{ $employeeBoot->employee_name }}"
                            width="60"
                            class="rounded"
                            style="cursor:pointer"
                            data-bs-toggle="modal"
                            data-bs-target="#employeeBoot{{ $employeeBoot->id }}"
                        >
                        @else
                        <span class="text-muted small">Tidak ada</span>
                        @endif
                    </td>
                    @role('Master Admin|Admin GA')
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editEmployeeBoot{{ $employeeBoot->id }}">
                            Edit
                        </button>
                        <form action="{{ route('employee-boots.destroy', $employeeBoot->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data sepatu ini?')">
                                Hapus
                            </button>
                        </form>
                    </td>
                    @endrole
                </tr>

                <div class="modal fade" id="employeeBoot{{ $employeeBoot->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Detail ABP Sepatu Boots</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                @if($employeeBoot->photo)
                                <img src="{{ asset('storage/' . $employeeBoot->photo) }}" alt="{{ $employeeBoot->employee_name }}" class="img-fluid rounded mb-3">
                                @endif

                                <div class="row g-3">
                                    <div class="col-md-6"><strong>Nama Pegawai:</strong> {{ $employeeBoot->employee_name }}</div>
                                    <div class="col-md-6"><strong>NIK/Kode Pegawai:</strong> {{ $employeeBoot->employee_code }}</div>
                                    <div class="col-md-6"><strong>Departemen:</strong> {{ $employeeBoot->department }}</div>
                                    <div class="col-md-6"><strong>Size Boot:</strong> {{ $employeeBoot->boot_size }}</div>
                                    <div class="col-md-6"><strong>Tanggal Pengembalian:</strong> {{ optional($employeeBoot->return_date)->format('d-m-Y') ?? '-' }}</div>
                                    <div class="col-md-6"><strong>Tanggal Habis:</strong> {{ optional($employeeBoot->expiry_date)->format('d-m-Y') ?? '-' }}</div>
                                    <div class="col-md-6"><strong>Sisa Hari:</strong> {{ $employeeBoot->remaining_days }} Hari</div>
                                    <div class="col-md-6"><strong>Status:</strong> {{ $employeeBoot->status_label }}</div>
                                    <div class="col-md-6"><strong>Jumlah Diberikan:</strong> {{ $employeeBoot->quantity_given }}</div>
                                    <div class="col-md-6"><strong>Kondisi:</strong> {{ $employeeBoot->condition }}</div>
                                    <div class="col-12"><strong>Keterangan:</strong> {{ $employeeBoot->notes }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @role('Master Admin|Admin GA')
                <div class="modal fade" id="editEmployeeBoot{{ $employeeBoot->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <form action="{{ route('employee-boots.update', $employeeBoot->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit ABP Sepatu Boots</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info">
                                        Tanggal pengembalian, tanggal habis, sisa hari, dan status tetap dihitung otomatis dari data awal.
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nama Pegawai</label>
                                            <input type="text" name="employee_name" class="form-control" value="{{ $employeeBoot->employee_name }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">NIK/Kode Pegawai</label>
                                            <input type="text" name="employee_code" class="form-control" value="{{ $employeeBoot->employee_code }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Departemen</label>
                                            <input type="text" name="department" class="form-control" value="{{ $employeeBoot->department }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Size Boot</label>
                                            <input type="text" name="boot_size" class="form-control" value="{{ $employeeBoot->boot_size }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Jumlah Diberikan</label>
                                            <input type="number" name="quantity_given" class="form-control" min="1" value="{{ $employeeBoot->quantity_given }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Kondisi</label>
                                            <select name="condition" class="form-select" required>
                                                <option value="Baru" @selected($employeeBoot->condition === 'Baru')>Baru</option>
                                                <option value="Bekas Layak" @selected($employeeBoot->condition === 'Bekas Layak')>Bekas Layak</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Keterangan</label>
                                            <select name="notes" class="form-select" required>
                                                <option value="Baru" @selected($employeeBoot->notes === 'Baru')>Baru</option>
                                                <option value="Distribusi Rutin" @selected($employeeBoot->notes === 'Distribusi Rutin')>Distribusi Rutin</option>
                                                <option value="Pergantian Rusak" @selected($employeeBoot->notes === 'Pergantian Rusak')>Pergantian Rusak</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Foto Baru</label>
                                            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Update Data</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endrole
                @empty
                <tr>
                    <td colspan="{{ $tableColumnCount }}" class="text-center text-muted">Belum ada data ABP Sepatu Boots.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($employeeBoots, 'links'))
    <div class="d-flex justify-content-center mt-3">
        {{ $employeeBoots->links('pagination::simple-bootstrap-5') }}
    </div>
    @endif
</div>

@role('Master Admin|Admin GA')
<div class="modal fade" id="createEmployeeBootModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('employee-boots.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah ABP Sepatu Boots</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        Tanggal pengembalian akan otomatis diisi hari ini, tanggal habis otomatis 180 hari, dan status dihitung otomatis.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Pegawai</label>
                            <input type="text" name="employee_name" class="form-control" value="{{ old('employee_name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NIK/Kode Pegawai</label>
                            <input type="text" name="employee_code" class="form-control" value="{{ old('employee_code') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Departemen</label>
                            <input type="text" name="department" class="form-control" value="{{ old('department') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Size Boot</label>
                            <input type="text" name="boot_size" class="form-control" value="{{ old('boot_size') }}" placeholder="Contoh: 42" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jumlah Diberikan</label>
                            <input type="number" name="quantity_given" class="form-control" min="1" value="{{ old('quantity_given', 1) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kondisi</label>
                            <select name="condition" class="form-select" required>
                                <option value="">Pilih kondisi</option>
                                <option value="Baru" @selected(old('condition') === 'Baru')>Baru</option>
                                <option value="Bekas Layak" @selected(old('condition') === 'Bekas Layak')>Bekas Layak</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Keterangan</label>
                            <select name="notes" class="form-select" required>
                                <option value="">Pilih keterangan</option>
                                <option value="Baru" @selected(old('notes') === 'Baru')>Baru</option>
                                <option value="Distribusi Rutin" @selected(old('notes') === 'Distribusi Rutin')>Distribusi Rutin</option>
                                <option value="Pergantian Rusak" @selected(old('notes') === 'Pergantian Rusak')>Pergantian Rusak</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Foto</label>
                            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endrole
@endsection
