@extends('adminlte::page')

@section('title', 'Audit Log')

@section('content_header')
    <h1><i class="fas fa-history mr-2"></i>Audit Log</h1>
@endsection

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Activity History</h3>
        <div class="card-tools">
            <small class="text-muted">Showing {{ $logs->total() }} entries</small>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card-body border-bottom pb-3">
        <form method="GET" action="{{ route('admin.activity-log.index') }}" class="row g-2">
            <div class="col-md-3">
                <select name="user_id" class="form-control form-control-sm">
                    <option value="">All Users</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="model_type" class="form-control form-control-sm">
                    <option value="">All Models</option>
                    @foreach($modelTypes as $m)
                        <option value="{{ $m }}" @selected(request('model_type') == $m)>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="action" class="form-control form-control-sm">
                    <option value="">All Actions</option>
                    @foreach($actions as $a)
                        <option value="{{ $a }}" @selected(request('action') == $a)>{{ ucfirst($a) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="{{ request('date_from') }}" placeholder="From">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="{{ request('date_to') }}" placeholder="To">
            </div>
            <div class="col-md-3 mt-2">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control"
                           value="{{ request('search') }}" placeholder="Search description…">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a href="{{ route('admin.activity-log.index') }}" class="btn btn-default">Reset</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:160px">When</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Record</th>
                    <th>Description</th>
                    <th style="width:60px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="text-muted small">{{ $log->created_at->format('d M Y H:i') }}</td>
                    <td>
                        @if($log->user)
                            <span class="font-weight-bold">{{ $log->user->name }}</span>
                        @else
                            <em class="text-muted">System</em>
                        @endif
                    </td>
                    <td>
                        @php
                            $badge = match($log->action) {
                                'created'  => 'success',
                                'updated'  => 'info',
                                'deleted'  => 'danger',
                                'restored' => 'warning',
                                default    => 'secondary',
                            };
                        @endphp
                        <span class="badge badge-{{ $badge }}">{{ $log->action }}</span>
                    </td>
                    <td>
                        <span class="text-muted small">{{ class_basename($log->model_type) }}</span><br>
                        <strong>{{ $log->model_label }}</strong>
                    </td>
                    <td class="small">{{ $log->description }}</td>
                    <td>
                        @if($log->old_values || $log->new_values)
                            <a href="{{ route('admin.activity-log.show', $log) }}"
                               class="btn btn-xs btn-outline-secondary">
                                <i class="fas fa-eye"></i> Diff
                            </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">No activity records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="card-footer">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
