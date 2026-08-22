@extends('adminlte::page')

@section('title', 'Funding Calls')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-bullhorn mr-2"></i>Funding Calls</h1>
        @if(auth()->user()->hasAnyRole(['coordinator','planning_officer']))
        <a href="{{ route('calls.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> Post New Call
        </a>
        @endif
    </div>
@endsection

@section('content')

{{-- Filters --}}
<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-filter mr-1"></i>Filter Calls</h3></div>
    <div class="card-body">
        <form method="GET" action="{{ route('calls.index') }}" class="form-inline flex-wrap" style="gap:10px;">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm" placeholder="Search title or theme..." style="min-width:220px;">
            <select name="status" class="form-control form-control-sm">
                <option value="">All Statuses</option>
                <option value="open"     {{ request('status') === 'open'     ? 'selected' : '' }}>Open</option>
                <option value="closed"   {{ request('status') === 'closed'   ? 'selected' : '' }}>Closed</option>
                <option value="draft"    {{ request('status') === 'draft'    ? 'selected' : '' }}>Draft</option>
                <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
            </select>
            <select name="donor_id" class="form-control form-control-sm">
                <option value="">All Donors</option>
                @foreach($donors as $donor)
                    <option value="{{ $donor->id }}" {{ request('donor_id') == $donor->id ? 'selected' : '' }}>
                        {{ $donor->acronym ?? $donor->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search mr-1"></i>Search</button>
            <a href="{{ route('calls.index') }}" class="btn btn-sm btn-default">Reset</a>
        </form>
    </div>
</div>

{{-- Calls Table --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $calls->total() }} Call(s) Found</h3>
    </div>
    <div class="card-body p-0">
        @if($calls->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fas fa-search fa-3x mb-3"></i>
                <h5>No funding calls found.</h5>
                @if(auth()->user()->hasAnyRole(['coordinator','planning_officer']))
                    <a href="{{ route('calls.create') }}" class="btn btn-primary mt-2">Post the First Call</a>
                @endif
            </div>
        @else
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th width="40">#</th>
                    <th>Title</th>
                    <th>Donor</th>
                    <th>Theme</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th width="60">Interest</th>
                    <th width="100">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($calls as $i => $call)
                <tr>
                    <td>{{ $calls->firstItem() + $i }}</td>
                    <td>
                        <a href="{{ route('calls.show', $call) }}" class="font-weight-bold text-dark">
                            {{ $call->title }}
                        </a>
                        @if($call->solicitation_number)
                            <br><small class="text-muted">Ref: {{ $call->solicitation_number }}</small>
                        @endif
                    </td>
                    <td><span class="badge badge-secondary">{{ $call->donor->acronym ?? $call->donor->name }}</span></td>
                    <td>{{ $call->theme ?? '-' }}</td>
                    <td>
                        @php $days = $call->days_until_deadline; @endphp
                        <strong>{{ $call->deadline->format('d M Y') }}</strong>
                        @if($call->status === 'open')
                            @if($days < 0)
                                <br><span class="badge badge-danger">Expired</span>
                            @elseif($days <= 7)
                                <br><span class="badge badge-danger">{{ $days }}d left</span>
                            @elseif($days <= 14)
                                <br><span class="badge badge-warning">{{ $days }}d left</span>
                            @else
                                <br><span class="badge badge-info">{{ $days }}d left</span>
                            @endif
                        @endif
                    </td>
                    <td>
                        @if($call->status === 'open') <span class="badge badge-success">Open</span>
                        @elseif($call->status === 'closed') <span class="badge badge-danger">Closed</span>
                        @elseif($call->status === 'draft') <span class="badge badge-warning">Draft</span>
                        @else <span class="badge badge-secondary">Archived</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge badge-info">{{ $call->interests_count }}</span>
                    </td>
                    <td>
                        <a href="{{ route('calls.show', $call) }}" class="btn btn-xs btn-outline-primary" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if(auth()->user()->hasAnyRole(['coordinator','planning_officer']))
                        <a href="{{ route('calls.edit', $call) }}" class="btn btn-xs btn-outline-warning" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @if($calls->hasPages())
    <div class="card-footer">{{ $calls->links() }}</div>
    @endif
</div>

@endsection
