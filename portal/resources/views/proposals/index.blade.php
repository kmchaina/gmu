@extends('adminlte::page')

@section('title', 'Proposals')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-file-alt mr-2"></i>Proposals</h1>
        @if(auth()->user()->hasAnyRole(['coordinator','planning_officer']))
        <a href="{{ route('proposals.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> Register Proposal
        </a>
        @endif
    </div>
@endsection

@section('content')

{{-- Filters --}}
<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-filter mr-1"></i>Filter</h3></div>
    <div class="card-body">
        <form method="GET" action="{{ route('proposals.index') }}" class="form-inline flex-wrap" style="gap:10px;">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm" placeholder="Search title..." style="min-width:220px;">
            <select name="status" class="form-control form-control-sm">
                <option value="">All Statuses</option>
                <option value="concept"           {{ request('status') === 'concept'           ? 'selected' : '' }}>Concept</option>
                <option value="under_development" {{ request('status') === 'under_development' ? 'selected' : '' }}>Under Development</option>
                <option value="internal_review"   {{ request('status') === 'internal_review'   ? 'selected' : '' }}>Internal Review</option>
                <option value="approved"          {{ request('status') === 'approved'          ? 'selected' : '' }}>Approved</option>
                <option value="submitted"         {{ request('status') === 'submitted'         ? 'selected' : '' }}>Submitted</option>
                <option value="awarded"           {{ request('status') === 'awarded'           ? 'selected' : '' }}>Awarded</option>
                <option value="declined"          {{ request('status') === 'declined'          ? 'selected' : '' }}>Declined</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search mr-1"></i>Search</button>
            <a href="{{ route('proposals.index') }}" class="btn btn-sm btn-default">Reset</a>
        </form>
    </div>
</div>

{{-- Proposals Table --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $proposals->total() }} Proposal(s)</h3>
    </div>
    <div class="card-body p-0">
        @if($proposals->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fas fa-file-alt fa-3x mb-3"></i>
                <h5>No proposals found.</h5>
            </div>
        @else
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th width="40">#</th>
                    <th>Title</th>
                    <th>Funding Call / Donor</th>
                    <th>Lead PI</th>
                    <th>Target Date</th>
                    <th>Status</th>
                    <th width="80" class="text-center">Reviews</th>
                    <th width="80">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($proposals as $i => $proposal)
                @php
                    $statusColors = [
                        'concept'           => 'secondary',
                        'under_development' => 'info',
                        'internal_review'   => 'warning',
                        'approved'          => 'primary',
                        'submitted'         => 'dark',
                        'awarded'           => 'success',
                        'declined'          => 'danger',
                        'withdrawn'         => 'light',
                    ];
                @endphp
                <tr>
                    <td>{{ $proposals->firstItem() + $i }}</td>
                    <td>
                        <a href="{{ route('proposals.show', $proposal) }}" class="font-weight-bold text-dark">
                            {{ Str::limit($proposal->title, 60) }}
                        </a>
                    </td>
                    <td>
                        @if($proposal->fundingCall)
                            <small>{{ Str::limit($proposal->fundingCall->title, 40) }}</small><br>
                            <span class="badge badge-secondary">{{ $proposal->fundingCall->donor->acronym ?? $proposal->fundingCall->donor->name }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>{{ $proposal->leadPi->name ?? '-' }}</td>
                    <td>
                        {{ $proposal->target_submission_date ? $proposal->target_submission_date->format('d M Y') : '-' }}
                    </td>
                    <td>
                        <span class="badge badge-{{ $statusColors[$proposal->status] ?? 'secondary' }}">
                            {{ ucfirst(str_replace('_', ' ', $proposal->status)) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $proposal->reviews_count >= 3 ? 'success' : 'warning' }}">
                            {{ $proposal->reviews_count }}/3
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('proposals.show', $proposal) }}" class="btn btn-xs btn-outline-primary" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if(auth()->user()->hasAnyRole(['coordinator','planning_officer']))
                        <a href="{{ route('proposals.edit', $proposal) }}" class="btn btn-xs btn-outline-warning" title="Edit">
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
    @if($proposals->hasPages())
    <div class="card-footer">{{ $proposals->links() }}</div>
    @endif
</div>

@endsection
