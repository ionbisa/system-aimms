@extends('layouts.app')

@section('content')
<h4>Audit Log</h4>

@if(isset($logs) && $logs->count())
<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Action</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td>{{ $log->created_at }}</td>
                <td>{{ $log->action }}</td>
                <td>{{ $log->description }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{ $logs->links() }}
@else
<p class="text-muted">Belum ada data audit log.</p>
@endif
@endsection
