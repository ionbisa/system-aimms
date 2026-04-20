<x-guest-layout>

@php
    $loginError = $errors->first('email') ?: $errors->first('password');
@endphp

<div class="aimms-login">

    <!-- LEFT PANEL -->
    <div class="login-left"
        style="
            background:
            linear-gradient(
                rgba(13,110,253,0.75),
                rgba(255,193,7,0.55)
            ),
            url('{{ asset('assets/img/login-bg.jpg') }}') center center / cover no-repeat;
        "
    >
        <div class="center-content">
            <h1 class="welcome-title">
                Welcome to 
                <span>AIMMS</span>
            </h1>

            <p class="welcome-desc">
                <strong>Asset & Inventory Management System</strong><br>
                <b>Sistem terintegrasi untuk mengelola aset, inventori,
                operasional, dan pelaporan perusahaan secara
                aman, cepat, dan terstruktur</b>.
            </p>

            <ul class="feature-list">
                <li><b>✔ Manajemen Asset & Inventory</b></li>
                <li><b>✔ Kontrol Role & Permission</b></li>
                <li><b>✔ Monitoring Operasional</b></li>
                <li><b>✔ Laporan Real-time</b></li>
            </ul>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="login-right">
        <div class="login-card">

            <div class="text-center mb-4">
                <img src="{{ asset('assets/img/logo.png') }}" height="70">
                <h4 class="fw-bold mt-3 text-primary">
                    AIMMS <span class="text-warning">Bangga Group</span>
                </h4>
            </div>

            <form method="POST" action="{{ route('login') }}" data-skip-loader>
                @csrf

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button class="btn btn-primary w-100">
                    Login
                </button>
            </form>

        </div>
    </div>

</div>

@if($loginError)
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2001;">
    <div id="loginErrorToast" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                {{ $loginError }} Silakan coba lagi.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toastElement = document.getElementById('loginErrorToast');

        if (!toastElement || typeof bootstrap === 'undefined') {
            alert(@json($loginError . ' Silakan coba lagi.'));
            return;
        }

        bootstrap.Toast.getOrCreateInstance(toastElement, {
            autohide: true,
            delay: 3500
        }).show();
    });
</script>
@endif

</x-guest-layout>
