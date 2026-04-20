@extends('layouts.app')

@section('content')
<div class="container">
    @php
        $columnLabels = [
            'no' => 'No',
            'no_po' => 'No PO',
            'tanggal' => 'Tanggal',
            'jenis_transaksi' => 'Jenis Transaksi',
            'divisi' => 'Divisi',
            'kategori' => 'Kategori',
            'uraian' => 'Uraian',
            'vendor' => 'Vendor',
            'qty' => 'Qty',
            'satuan' => 'Satuan',
            'harga_satuan' => 'Harga Satuan',
            'total_harga' => 'Total Harga',
            'keterangan' => 'Keterangan',
            'kode_barang' => 'Kode Barang',
            'nama_barang' => 'Nama Barang',
            'lokasi' => 'Lokasi',
            'status' => 'Status',
            'tanggal_update' => 'Tanggal Update',
        ];
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">{{ $title }}</h4>
            <small class="text-muted">Pilih jenis data dan rentang tanggal sesuai kebutuhan, lalu unduh Excel atau cetak/simpan sebagai PDF.</small>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.' . $preset) }}" class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label">Jenis Data</label>
            <select name="type" class="form-select">
                @foreach($reportTypes as $reportKey => $reportLabel)
                <option value="{{ $reportKey }}" @selected($filters['type'] === $reportKey)>{{ $reportLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tanggal Awal</label>
            <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Tanggal Akhir</label>
            <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Divisi</label>
            <select name="division" class="form-select">
                <option value="">Semua Divisi</option>
                @foreach($divisionOptions as $divisionOption)
                <option value="{{ $divisionOption }}" @selected(($filters['division'] ?? '') === $divisionOption)>{{ $divisionOption }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-auto align-self-end">
            <button type="submit" class="btn btn-primary">Tampilkan</button>
            <a href="{{ route('reports.' . $preset) }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="d-flex gap-2 mb-3">
        <a href="{{ route('reports.export-excel', array_merge(['preset' => $preset], $filters)) }}" class="btn btn-success">
            Download Excel (.csv)
        </a>
        <a href="{{ route('reports.print', array_merge(['preset' => $preset], $filters)) }}" target="_blank" class="btn btn-danger">
            Cetak / Simpan PDF
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    @foreach($columns as $column)
                    <th>{{ $columnLabels[$column] ?? ucfirst(str_replace('_', ' ', $column)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($reportRows as $row)
                <tr>
                    @foreach($columns as $column)
                    <td>{{ $row[$column] ?? '-' }}</td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="text-center text-muted">Tidak ada data pada filter yang dipilih.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
