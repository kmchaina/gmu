@extends('adminlte::page')

@section('title', 'Reports')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-file-invoice mr-2"></i>Reports</h1>
        <a href="{{ route('reports.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> Add Report
        </a>
    </div>
@endsection

@section('content')

{{-- Filters --}}
<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-filter mr-1"></i>Filter</h3></div>
    <div class="card-body">
        <form method="GET" action="{{ route('reports.index') }}" class="form-inline flex-wrap" style="gap:10px;">
            <select name="type" class="form-control form-control-sm">
                <option value="">All Types</option>
                <option value="technical"  {{ request('type') === 'technical'  ? 'selected' : '' }}>Technical</option>
                <option value="financial"  {{ request('type') === 'financial'  ? 'selected' : '' }}>Financial</option>
                <option value="combined"   {{ request('type') === 'combined'   ? 'selected' : '' }}>Combined</option>
            </select>
            <select name="status" class="form-control form-control-sm">
                <option value="">All Statuses</option>
                <option value="pending"      {{ request('status') === 'pending'      ? 'selected' : '' }}>Pending</option>
                <option value="submitted"    {{ request('status') === 'submitted'    ? 'selected' : '' }}>Submitted</option>
                <option value="under_review" {{ request('status') === 'under_review' ? 'selected' : '' }}>Under Review</option>
                <option value="approved"     {{ request('status') === 'approved'     ? 'selected' : '' }}>Approved</option>
                <option value="rejected"     {{ request('status') === 'rejected'     ? 'selected' : '' }}>Rejected</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search mr-1"></i>Filter</button>
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-default">Reset</a>
        </form>
    </div>
</div>

{{-- Reports Table --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $reports->total() }} Report(s)</h3>
    </div>
    <div class="card-body p-0">
        @if($reports->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fas fa-file-invoice fa-3x mb-3"></i>
                <h5>No reports found.</h5>
            </div>
        @else
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Title / Award</th>
                    <th>Type</th>
                    <th>Period</th>
                    <th>Due Date</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th width="80">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reports as $report)
                @php $overdue = $report->due_date->isPast() && $report->status === 'pending'; @endphp
                <tr class="{{ $overdue ? 'table-danger' : '' }}">
                    <td>
                        <a href="{{ route('reports.show', $report) }}" class="font-weight-bold text-dark">
                            {{ Str::limit($report->title, 50) }}
                        </a>
                        <br>
                        <small class="text-muted">
                            <a href="{{ route('awards.show', $report->award) }}">{{ Str::limit($report->award->title, 40) }}</a>
                        </small>
                    </td>
                    <td><span class="badge badge-secondary">{{ ucfirst($report->report_type) }}</span></td>
                    <td>
                        @if($report->period_start && $report->period_end)
                            <small>{{ $report->period_start->format('M Y') }} – {{ $report->period_end->format('M Y') }}</small>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <strong class="{{ $overdue ? 'text-danger' : '' }}">
                            {{ $report->due_date->format('d M Y') }}
                        </strong>
                        @if($overdue)
                            <br><span class="badge badge-danger">Overdue</span>
                        @endif
                    </td>
                    <td>{{ $report->submitted_date ? $report->submitted_date->format('d M Y') : '-' }}</td>
                    <td>
                        @if($report->status === 'approved') <span class="badge badge-success">Approved</span>
                        @elseif($report->status === 'submitted') <span class="badge badge-info">Submitted</span>
                        @elseif($report->status === 'under_review') <span class="badge badge-warning">Under Review</span>
                        @elseif($report->status === 'rejected') <span class="badge badge-danger">Rejected</span>
                        @else <span class="badge badge-secondary">Pending</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('reports.show', $report) }}" class="btn btn-xs btn-outline-primary" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('reports.edit', $report) }}" class="btn btn-xs btn-outline-warning" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @if($reports->hasPages())
    <div class="card-footer">{{ $reports->links() }}</div>
    @endif
</div>

@endsection
