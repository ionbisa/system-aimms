<div class="alert alert-warning">
    Menghapus akun akan menghapus akses login akun ini secara permanen. Masukkan password saat ini untuk konfirmasi.
</div>

<form method="POST" action="{{ route('profile.destroy') }}" class="row g-3">
    @csrf
    @method('DELETE')

    <div class="col-md-6">
        <label for="delete_profile_password" class="form-label">Password Saat Ini</label>
        <input id="delete_profile_password" name="password" type="password" class="form-control" required>
        @if($errors->userDeletion->has('password'))
        <div class="text-danger small mt-1">{{ $errors->userDeletion->first('password') }}</div>
        @endif
    </div>

    <div class="col-12">
        <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus akun Anda sendiri?')">
            Hapus Akun
        </button>
    </div>
</form>
