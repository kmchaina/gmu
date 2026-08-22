@extends('adminlte::page')

@section('title', $report->title)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-file-invoice mr-2"></i>{{ Str::limit($report->title, 60) }}</h1>
        <div>
            <a href="{{ route('reports.edit', $report) }}" class="btn btn-warning">
                <i class="fas fa-edit mr-1"></i>Edit
            </a>
            <a href="{{ route('reports.index') }}" class="btn btn-default ml-1">
                <i class="fas fa-arrow-left mr-1"></i>Back
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="row">

    <div class="col-md-8">

        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Report Details</h3>
                <div class="card-tools">
                    @if($report->status === 'approved') <span class="badge badge-success badge-lg">Approved</span>
                    @elseif($report->status === 'submitted') <span class="badge badge-info badge-lg">Submitted</span>
                    @elseif($report->status === 'under_review') <span class="badge badge-warning badge-lg">Under Review</span>
                    @elseif($report->status === 'rejected') <span class="badge badge-danger badge-lg">Rejected</span>
                    @else
                        @php $overdue = $report->due_date->isPast(); @endphp
                        <span class="badge badge-{{ $overdue ? 'danger' : 'secondary' }} badge-lg">
                            {{ $overdue ? 'Overdue' : 'Pending' }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="200" class="text-muted">Award</th>
                        <td>
                            <a href="{{ route('awards.show', $report->award) }}">{{ $report->award->title }}</a>
                            <br><small class="text-muted">{{ $report->award->donor->name }}</small>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted">Report Type</th>
                        <td><span class="badge badge-secondary">{{ ucfirst($report->report_type) }}</span></td>
                    </tr>
                    @if($report->period_start && $report->period_end)
                    <tr>
                        <th class="text-muted">Reporting Period</th>
                        <td>{{ $report->period_start->format('d M Y') }} — {{ $report->period_end->format('d M Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th class="text-muted">Due Date</th>
                        <td>
                            <strong class="{{ $report->due_date->isPast() && $report->status === 'pending' ? 'text-danger' : '' }}">
                                {{ $report->due_date->format('d F Y') }}
                            </strong>
                        </td>
                    </tr>
                    @if($report->submitted_date)
                    <tr>
                        <th class="text-muted">Submitted On</th>
                        <td>{{ $report->submitted_date->format('d F Y') }}</td>
                    </tr>
                    @endif
                    @if($report->submittedBy)
                    <tr>
                        <th class="text-muted">Submitted By</th>
                        <td>{{ $report->submittedBy->name }}</td>
                    </tr>
                    @endif
                    @if($report->verifiedBy)
                    <tr>
                        <th class="text-muted">Verified By</th>
                        <td>{{ $report->verifiedBy->name }} on {{ $report->verified_at?->format('d M Y') }}</td>
                    </tr>
                    @endif
                </table>

                @if($report->gmu_comments)
                <hr>
                <h6 class="font-weight-bold">GMU Comments</h6>
                <p class="text-muted">{{ $report->gmu_comments }}</p>
                @endif

                @if($report->file_path)
                <hr>
                <a href="{{ route('reports.download', $report) }}" target="_blank" class="btn btn-outline-primary">
                    <i class="fas fa-download mr-1"></i>Download Report File
                </a>
                @endif
            </div>
        </div>

    </div>

    <div class="col-md-4">

        <div class="card">
            <div class="card-header"><h3 class="card-title">Actions</h3></div>
            <div class="card-body">
                <a href="{{ route('reports.edit', $report) }}" class="btn btn-warning btn-block mb-2">
                    <i class="fas fa-edit mr-1"></i>Edit Report
                </a>
                <a href="{{ route('awards.show', $report->award) }}" class="btn btn-outline-success btn-block">
                    <i class="fas fa-trophy mr-1"></i>View Award
                </a>

                @if(auth()->user()->hasAnyRole(['coordinator','planning_officer']) && $report->status === 'submitted')
                <hr>
                <form action="{{ route('reports.update', $report) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="award_id" value="{{ $report->award_id }}">
                    <input type="hidden" name="report_type" value="{{ $report->report_type }}">
                    <input type="hidden" name="title" value="{{ $report->title }}">
                    <input type="hidden" name="due_date" value="{{ $report->due_date->format('Y-m-d') }}">
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" class="btn btn-success btn-block"
                            onclick="return confirm('Approve this report?')">
                        <i class="fas fa-check mr-1"></i>Approve Report
                    </button>
                </form>
                @endif
            </div>
        </div>

        <div class="card bg-light">
            <div class="card-body">
                <p class="small text-muted mb-1">
                    Lead PI: <strong>{{ $report->award->proposal->leadPi->name ?? '-' }}</strong>
                </p>
                <p class="small text-muted mb-0">
                    Contract: <strong>{{ $report->award->contract_number }}</strong>
                </p>
            </div>
        </div>

    </div>
</div>
@endsection
