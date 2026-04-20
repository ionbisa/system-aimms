@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Setting User</h4>
            <small class="text-muted">Kelola akun user, role, hapus user, dan reset password hanya untuk Master Admin.</small>
        </div>

        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
            Tambah User
        </button>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('generated_password'))
    <div class="alert alert-warning">
        <strong>Password baru user:</strong> <code>{{ session('generated_password') }}</code>
        <div class="small mt-1 text-muted">Password ini hanya ditampilkan sekali. Silakan catat atau informasikan ke user terkait.</div>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger">
        <strong>Proses belum berhasil:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="GET" action="{{ route('users.index') }}" class="row g-2 mb-3">
        <div class="col-md-5">
            <input
                type="text"
                name="search"
                value="{{ $search ?? '' }}"
                class="form-control"
                placeholder="Cari nama atau email user"
            >
        </div>
        <div class="col-md-auto">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th>No</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role / Level</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $index => $user)
                <tr>
                    <td class="text-center">{{ method_exists($users, 'firstItem') ? $users->firstItem() + $index : $index + 1 }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($user->profile_photo_path)
                            <img
                                src="{{ asset('storage/' . $user->profile_photo_path) }}"
                                alt="{{ $user->name }}"
                                class="rounded-circle border"
                                width="42"
                                height="42"
                                style="object-fit: cover;"
                            >
                            @else
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center border bg-light text-secondary"
                                 style="width: 42px; height: 42px;">
                                <i class="bi bi-person-fill fs-5"></i>
                            </div>
                            @endif
                            <div>
                                <div class="fw-semibold">{{ $user->name }}</div>
                                <small class="text-muted">{{ $user->profile_photo_path ? 'Foto user aktif' : 'Ikon user default' }}</small>
                            </div>
                        </div>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @forelse($user->roles as $role)
                        <span class="badge bg-primary">{{ $role->name }}</span>
                        @empty
                        <span class="text-muted">Belum ada role</span>
                        @endforelse
                    </td>
                    <td class="text-center">{{ optional($user->created_at)->format('d-m-Y') ?? '-' }}</td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editUser{{ $user->id }}">
                            Edit
                        </button>
                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#resetPassword{{ $user->id }}">
                            Reset Password
                        </button>
                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus akun user ini?')">
                                Hapus
                            </button>
                        </form>
                    </td>
                </tr>

                <div class="modal fade" id="editUser{{ $user->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <form action="{{ route('users.update', $user->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Akun User</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nama User</label>
                                            <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Role / Level</label>
                                            <select name="role" class="form-select" required>
                                                @foreach($roles as $role)
                                                <option value="{{ $role }}" @selected($user->roles->contains('name', $role))>{{ $role }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Upload Foto User</label>
                                            <input type="file" name="profile_photo" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Update User</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="resetPassword{{ $user->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <form action="{{ route('users.reset-password', $user->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="modal-header">
                                    <h5 class="modal-title">Reset Password User</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info">
                                        Masukkan password baru untuk user yang lupa password.
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Password Baru</label>
                                            <input type="password" name="password" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Konfirmasi Password Baru</label>
                                            <input type="password" name="password_confirmation" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button
                                        type="submit"
                                        formaction="{{ route('users.reset-password-default', $user->id) }}"
                                        formmethod="POST"
                                        class="btn btn-outline-warning"
                                        onclick="event.preventDefault(); this.closest('form').querySelector('input[name=_method]').value='PATCH'; this.closest('form').action='{{ route('users.reset-password-default', $user->id) }}'; this.closest('form').submit();"
                                    >
                                        Set Default `password123`
                                    </button>
                                    <button type="submit" class="btn btn-primary">Simpan Password Baru</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">Belum ada data user.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($users, 'links'))
    <div class="d-flex justify-content-center mt-3">
        {{ $users->links('pagination::simple-bootstrap-5') }}
    </div>
    @endif
</div>

<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama User</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role / Level</label>
                            <select name="role" class="form-select" required>
                                <option value="">Pilih role</option>
                                @foreach($roles as $role)
                                <option value="{{ $role }}" @selected(old('role') === $role)>{{ $role }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Upload Foto User</label>
                            <input type="file" name="profile_photo" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
