@extends('layouts.app')

@section('content')
@php
    $currentMonth = $currentMonth ?? \Carbon\Carbon::today();
    $stockMasuk = $stockMasuk ?? 0;
    $stockKeluar = $stockKeluar ?? 0;
    $stockSaatIni = $stockSaatIni ?? 0;
    $totalPengeluaran = $totalPengeluaran ?? 0;
    $currentYear = $currentYear ?? \Carbon\Carbon::today()->year;
    $assetKendaraan = $assetKendaraan ?? 0;
    $assetOffice = $assetOffice ?? 0;
    $assetActive = $assetActive ?? 0;
    $assetBroken = $assetBroken ?? 0;
    $barangMasukHariIni = $barangMasukHariIni ?? collect();
    $barangKeluarHariIni = $barangKeluarHariIni ?? collect();
    $todayPurchase = $todayPurchase ?? collect();
    $monthlyExpenseLabels = $monthlyExpenseLabels ?? [];
    $monthlyExpenseValues = $monthlyExpenseValues ?? [];
@endphp
<h4 class="mb-4 fw-bold">Asset Inventory Maintenance and Management System</h4>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white shadow">
            <div class="card-body">
                <small>Barang Masuk {{ $currentMonth->translatedFormat('F Y') }}</small>
                <h3>{{ number_format($stockMasuk) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-success text-white shadow">
            <div class="card-body">
                <small>Barang Keluar {{ $currentMonth->translatedFormat('F Y') }}</small>
                <h3>{{ number_format($stockKeluar) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-warning text-dark shadow">
            <div class="card-body">
                <small>Stock Saat Ini</small>
                <h3>{{ number_format($stockSaatIni) }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-danger text-white shadow">
            <div class="card-body">
                <small>Total Pengeluaran {{ $currentMonth->translatedFormat('F Y') }}</small>
                <h4>Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card shadow h-100">
            <div class="card-body">
                <h6 class="mb-3">Grafik Pengeluaran Biaya {{ $currentYear }}</h6>
                <div style="height: 280px;">
                    <canvas id="expenseLineChart"></canvas>
                </div>
                <small class="text-muted">Setiap transaksi pengeluaran yang dicatat pada menu Purchase Order akan otomatis masuk ke akumulasi bulan terkait.</small>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-body">
                <h6>Asset Kendaraan</h6>
                <div class="mx-auto" style="max-width: 240px; height: 240px;">
                    <canvas id="kendaraanChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-body">
                <h6>Company Assets</h6>
                <div class="mx-auto" style="max-width: 240px; height: 240px;">
                    <canvas id="officeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <h6>Barang Masuk Hari Ini</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Barang</th>
                        <th>Qty</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($barangMasukHariIni as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row->item_name }}</td>
                        <td>{{ $row->qty }}</td>
                        <td>{{ \Carbon\Carbon::parse($row->created_at)->translatedFormat('d-m-Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">Belum ada data barang masuk hari ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <h6>Barang Keluar Hari Ini</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Barang</th>
                        <th>Qty</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($barangKeluarHariIni as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row->item_name }}</td>
                        <td>{{ $row->qty }}</td>
                        <td>{{ \Carbon\Carbon::parse($row->created_at)->translatedFormat('d-m-Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">Belum ada data barang keluar hari ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <h6>Purchase Order Hari Ini</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>PO Number</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($todayPurchase as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row->po_number }}</td>
                        <td>{{ $row->display_status }}</td>
                        <td>Rp {{ number_format((float) $row->total_price, 0, ',', '.') }}</td>
                        <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d-m-Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">Belum ada data hari ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const monthlyExpenseValues = @json($monthlyExpenseValues);
    const suggestedExpenseMax = monthlyExpenseValues.length
        ? Math.max(...monthlyExpenseValues, 0) * 1.15 || 1000000
        : 1000000;

    new Chart(document.getElementById('expenseLineChart'), {
        type: 'line',
        data: {
            labels: @json($monthlyExpenseLabels),
            datasets: [{
                label: 'Total Pengeluaran',
                data: monthlyExpenseValues,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.12)',
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(context.parsed.y || 0));
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: suggestedExpenseMax,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(value));
                        }
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('kendaraanChart'), {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Maintenance'],
            datasets: [{
                data: [{{ $assetKendaraan }}, {{ $assetBroken }}],
                backgroundColor: ['#0d6efd', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%'
        }
    });

    new Chart(document.getElementById('officeChart'), {
        type: 'doughnut',
        data: {
            labels: ['Office Assets', 'Active Assets'],
            datasets: [{
                data: [{{ $assetOffice }}, {{ $assetActive }}],
                backgroundColor: ['#198754', '#ffc107']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%'
        }
    });
});
</script>
@endsection
