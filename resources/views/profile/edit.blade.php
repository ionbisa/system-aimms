@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center">
                    @if($user->profile_photo_url)
                    <img
                        src="{{ $user->profile_photo_url }}"
                        alt="{{ $user->name }}"
                        class="rounded-circle border mb-3"
                        width="110"
                        height="110"
                        style="object-fit: cover;"
                    >
                    @else
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center border bg-light text-secondary mb-3"
                         style="width: 110px; height: 110px;">
                        <i class="bi bi-person-fill" style="font-size: 42px;"></i>
                    </div>
                    @endif

                    <h4 class="mb-1">{{ $user->name }}</h4>
                    <div class="text-muted mb-2">{{ $user->email }}</div>
                    <div>
                        @forelse($user->roles as $role)
                        <span class="badge bg-primary">{{ $role->name }}</span>
                        @empty
                        <span class="badge bg-secondary">Belum ada role</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            @if (session('status') === 'profile-updated')
            <div class="alert alert-success">Profil berhasil diperbarui.</div>
            @endif

            @if (session('status') === 'password-updated')
            <div class="alert alert-success">Password berhasil diperbarui.</div>
            @endif

            @if($errors->any())
            <div class="alert alert-danger">
                <strong>Perubahan belum berhasil disimpan:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Informasi Akun</h5>
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="row g-3">
                        @csrf
                        @method('PATCH')

                        <div class="col-md-6">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" value="{{ $user->name }}" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="{{ $user->email }}" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Level / Role</label>
                            <input type="text" class="form-control" value="{{ $user->roles->pluck('name')->implode(', ') ?: 'Belum ada role' }}" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Upload Foto Akun</label>
                            <input type="file" name="profile_photo" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif,image/avif,.jpg,.jpeg,.png,.webp,.gif,.avif">
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan Profil</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Ubah Password</h5>
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
