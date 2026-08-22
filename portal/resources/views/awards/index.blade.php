@extends('adminlte::page')

@section('title', 'Awards')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-trophy mr-2"></i>Awards</h1>
        @if(auth()->user()->hasAnyRole(['coordinator','planning_officer']))
        <a href="{{ route('awards.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> New Award
        </a>
        @endif
    </div>
@endsection

@section('content')

{{-- Filters --}}
<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-filter mr-1"></i>Filter</h3></div>
    <div class="card-body">
        <form method="GET" action="{{ route('awards.index') }}" class="form-inline flex-wrap" style="gap:10px;">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm" placeholder="Title or contract number..." style="min-width:220px;">
            <select name="status" class="form-control form-control-sm">
                <option value="">All Statuses</option>
                <option value="active"      {{ request('status') === 'active'      ? 'selected' : '' }}>Active</option>
                <option value="completed"   {{ request('status') === 'completed'   ? 'selected' : '' }}>Completed</option>
                <option value="suspended"   {{ request('status') === 'suspended'   ? 'selected' : '' }}>Suspended</option>
                <option value="terminated"  {{ request('status') === 'terminated'  ? 'selected' : '' }}>Terminated</option>
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
            <a href="{{ route('awards.index') }}" class="btn btn-sm btn-default">Reset</a>
        </form>
    </div>
</div>

{{-- Awards Table --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $awards->total() }} Award(s)</h3>
    </div>
    <div class="card-body p-0">
        @if($awards->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fas fa-trophy fa-3x mb-3"></i>
                <h5>No awards found.</h5>
            </div>
        @else
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th width="50">Health</th>
                    <th>Title / Contract</th>
                    <th>Donor</th>
                    <th>Lead PI</th>
                    <th>Period</th>
                    <th>Budget</th>
                    <th>Status</th>
                    <th width="60">Reports</th>
                    <th width="80">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($awards as $award)
                @php $tl = $award->traffic_light; @endphp
                <tr>
                    <td class="text-center">
                        <span class="badge badge-{{ $tl === 'green' ? 'success' : ($tl === 'yellow' ? 'warning' : 'danger') }}"
                              title="{{ ucfirst($tl) }} status">
                            <i class="fas fa-circle"></i>
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('awards.show', $award) }}" class="font-weight-bold text-dark">
                            {{ Str::limit($award->title, 55) }}
                        </a>
                        <br><small class="text-muted">{{ $award->contract_number }}</small>
                    </td>
                    <td>
                        <span class="badge badge-secondary">{{ $award->donor->acronym ?? $award->donor->name }}</span>
                    </td>
                    <td>{{ $award->proposal->leadPi->name ?? '-' }}</td>
                    <td>
                        <small>
                            {{ $award->start_date->format('M Y') }} – {{ $award->end_date->format('M Y') }}
                        </small>
                    </td>
                    <td>
                        <strong>{{ number_format($award->total_budget, 0) }}</strong>
                        <small class="text-muted">{{ $award->currency }}</small>
                    </td>
                    <td>
                        @if($award->status === 'active') <span class="badge badge-success">Active</span>
                        @elseif($award->status === 'completed') <span class="badge badge-primary">Completed</span>
                        @elseif($award->status === 'suspended') <span class="badge badge-warning">Suspended</span>
                        @else <span class="badge badge-danger">Terminated</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge badge-info">{{ $award->reports_count }}</span>
                    </td>
                    <td>
                        <a href="{{ route('awards.show', $award) }}" class="btn btn-xs btn-outline-primary" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if(auth()->user()->hasAnyRole(['coordinator','planning_officer','finance_officer']))
                        <a href="{{ route('awards.edit', $award) }}" class="btn btn-xs btn-outline-warning" title="Edit">
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
    @if($awards->hasPages())
    <div class="card-footer">{{ $awards->links() }}</div>
    @endif
</div>

@endsection
