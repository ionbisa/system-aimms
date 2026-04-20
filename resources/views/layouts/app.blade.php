<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>AIMMS - Bangga Group</title>

     <!-- FAVICON -->
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo.png') }}">
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell page-loading">

@include('layouts.partials.page-loader')

@include('layouts.partials.header')

<div class="app-body">
    @include('layouts.partials.sidebar')

    <main class="app-main">
        <div class="app-content">
            @yield('content')
        </div>
    </main>
</div>

@include('layouts.partials.footer')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
