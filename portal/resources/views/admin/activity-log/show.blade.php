@extends('adminlte::page')

@section('title', 'Audit Log Entry')

@section('content_header')
    <h1>
        <i class="fas fa-history mr-2"></i>Audit Entry
        <small class="text-muted">#{{ $log->id }}</small>
    </h1>
@endsection

@section('content')
<div class="row">
    <div class="col-md-5">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Details</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><th class="w-35 pl-3">When</th><td>{{ $log->created_at->format('d M Y H:i:s') }}</td></tr>
                    <tr><th class="pl-3">User</th><td>{{ $log->user?->name ?? 'System' }}</td></tr>
                    <tr><th class="pl-3">Action</th>
                        <td>
                            @php $badge = match($log->action) { 'created'=>'success','updated'=>'info','deleted'=>'danger','restored'=>'warning',default=>'secondary' }; @endphp
                            <span class="badge badge-{{ $badge }}">{{ $log->action }}</span>
                        </td>
                    </tr>
                    <tr><th class="pl-3">Model</th><td>{{ class_basename($log->model_type) }} (ID {{ $log->model_id }})</td></tr>
                    <tr><th class="pl-3">Record</th><td><strong>{{ $log->model_label }}</strong></td></tr>
                    <tr><th class="pl-3">Description</th><td>{{ $log->description }}</td></tr>
                    <tr><th class="pl-3">IP Address</th><td><code>{{ $log->ip_address ?? '—' }}</code></td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        @if($log->old_values || $log->new_values)
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Field Changes</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="pl-3">Field</th>
                            <th><span class="text-danger">Before</span></th>
                            <th><span class="text-success">After</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $fields = array_unique(array_merge(
                                array_keys($log->old_values ?? []),
                                array_keys($log->new_values ?? [])
                            ));
                        @endphp
                        @foreach($fields as $field)
                        <tr>
                            <td class="pl-3 font-weight-bold small text-muted">{{ $field }}</td>
                            <td class="small">
                                <span class="text-danger">
                                    {{ $log->old_values[$field] ?? '—' }}
                                </span>
                            </td>
                            <td class="small">
                                <span class="text-success">
                                    {{ $log->new_values[$field] ?? '—' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="alert alert-light">No field-level diff recorded for this entry.</div>
        @endif
    </div>
</div>

<a href="{{ route('admin.activity-log.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left mr-1"></i> Back to Audit Log
</a>
@endsection
