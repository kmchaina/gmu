@extends('adminlte::page')

@section('title', $award->title)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-trophy mr-2"></i>{{ Str::limit($award->title, 60) }}</h1>
        <div>
            @if(auth()->user()->hasAnyRole(['coordinator','planning_officer','finance_officer']))
            <a href="{{ route('awards.edit', $award) }}" class="btn btn-warning">
                <i class="fas fa-edit mr-1"></i>Edit
            </a>
            @endif
            <a href="{{ route('awards.index') }}" class="btn btn-default ml-1">
                <i class="fas fa-arrow-left mr-1"></i>Back
            </a>
        </div>
    </div>
@endsection

@section('content')

@php
    $tl       = $award->traffic_light;
    $tlColor  = ['green' => 'success', 'yellow' => 'warning', 'red' => 'danger'][$tl];
    $tlLabel  = ['green' => 'On Track',  'yellow' => 'Attention Needed', 'red' => 'Action Required'][$tl];
    $totalAllocated = $award->budgets->sum('allocated_amount');
    $totalSpent     = $award->budgets->sum('spent_amount');
    $burnRate       = $totalAllocated > 0 ? ($totalSpent / $totalAllocated) * 100 : 0;
    $canManage      = auth()->user()->hasAnyRole(['coordinator','planning_officer','finance_officer']);
@endphp

<div class="row">

{{-- ============================================================ LEFT COLUMN ============================================================ --}}
<div class="col-md-8">

    {{-- Core Info --}}
    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title">Award Information</h3>
            <div class="card-tools">
                <span class="badge badge-lg badge-{{ $tlColor }} mr-2">
                    <i class="fas fa-circle mr-1"></i>{{ $tlLabel }}
                </span>
                @if($award->status === 'active')      <span class="badge badge-success badge-lg">Active</span>
                @elseif($award->status === 'completed') <span class="badge badge-primary badge-lg">Completed</span>
                @elseif($award->status === 'suspended') <span class="badge badge-warning badge-lg">Suspended</span>
                @else                                   <span class="badge badge-danger badge-lg">Terminated</span>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm table-borderless">
                <tr>
                    <th width="200" class="text-muted">Donor</th>
                    <td>
                        <a href="{{ route('donors.show', $award->donor) }}">
                            <strong>{{ $award->donor->name }}</strong>
                            @if($award->donor->acronym) ({{ $award->donor->acronym }}) @endif
                        </a>
                    </td>
                </tr>
                <tr>
                    <th class="text-muted">Contract Number</th>
                    <td><code>{{ $award->contract_number }}</code></td>
                </tr>
                <tr>
                    <th class="text-muted">Lead PI</th>
                    <td><strong>{{ $award->proposal->leadPi->name ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <th class="text-muted">Project Period</th>
                    <td>
                        {{ $award->start_date->format('d M Y') }} — {{ $award->end_date->format('d M Y') }}
                        <span class="text-muted small ml-2">({{ $award->start_date->diffInMonths($award->end_date) }} months)</span>
                    </td>
                </tr>
                @if($award->kickoff_date)
                <tr>
                    <th class="text-muted">Kickoff Date</th>
                    <td>{{ $award->kickoff_date->format('d M Y') }}</td>
                </tr>
                @endif
                <tr>
                    <th class="text-muted">Linked Proposal</th>
                    <td><a href="{{ route('proposals.show', $award->proposal) }}">{{ Str::limit($award->proposal->title, 60) }}</a></td>
                </tr>
                @if($award->signed_contract)
                <tr>
                    <th class="text-muted">Signed Contract</th>
                    <td>
                        <a href="{{ route('awards.contract', $award) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-file-pdf mr-1"></i>Download
                        </a>
                    </td>
                </tr>
                @endif
            </table>
            @if($award->notes)
            <hr>
            <h6 class="font-weight-bold">Notes</h6>
            <p class="text-muted">{{ $award->notes }}</p>
            @endif
        </div>
    </div>

    {{-- ======== BUDGET ======== --}}
    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-dollar-sign mr-1"></i>Budget</h3>
            <div class="card-tools">
                <span class="badge badge-{{ $burnRate > 100 ? 'danger' : ($burnRate > 90 ? 'warning' : 'info') }}">
                    {{ number_format($burnRate, 1) }}% utilised
                </span>
                @if($canManage)
                <button class="btn btn-xs btn-primary ml-2" data-toggle="collapse" data-target="#addBudgetForm">
                    <i class="fas fa-plus"></i> Add Line
                </button>
                @endif
            </div>
        </div>
        <div class="card-body p-0">

            @if($canManage)
            <div class="collapse" id="addBudgetForm">
                <div class="p-3 bg-light border-bottom">
                    <form action="{{ route('awards.budgets.store', $award) }}" method="POST">
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label class="small">Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-control form-control-sm">
                                    <option value="personnel">Personnel</option>
                                    <option value="equipment">Equipment</option>
                                    <option value="travel">Travel</option>
                                    <option value="supplies">Supplies &amp; Consumables</option>
                                    <option value="subcontract">Subcontract</option>
                                    <option value="indirect">Indirect / Overhead</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="small">Allocated ({{ $award->currency }}) <span class="text-danger">*</span></label>
                                <input type="number" name="allocated_amount" step="0.01" min="0"
                                       class="form-control form-control-sm" placeholder="0.00">
                            </div>
                            <div class="col-md-3">
                                <label class="small">Spent ({{ $award->currency }})</label>
                                <input type="number" name="spent_amount" step="0.01" min="0"
                                       class="form-control form-control-sm" placeholder="0.00">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary btn-sm btn-block">Add</button>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-md-9">
                                <input type="text" name="notes" class="form-control form-control-sm"
                                       placeholder="Notes (optional)">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            @if($award->budgets->count() > 0)
            @if($totalAllocated > 0)
            <div class="px-3 pt-2">
                <div class="progress mb-0" style="height:14px;">
                    <div class="progress-bar bg-{{ $burnRate > 100 ? 'danger' : ($burnRate > 90 ? 'warning' : 'success') }}"
                         role="progressbar" style="width: {{ min($burnRate, 100) }}%">
                        {{ number_format($burnRate, 0) }}%
                    </div>
                </div>
            </div>
            @endif
            <table class="table table-sm table-bordered mb-0 mt-2">
                <thead class="thead-light">
                    <tr>
                        <th>Category</th>
                        <th class="text-right">Allocated</th>
                        <th class="text-right">Spent</th>
                        <th class="text-right">Remaining</th>
                        <th class="text-center">%</th>
                        @if($canManage)<th width="40"></th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($award->budgets as $budget)
                    @php $pct = $budget->allocated_amount > 0 ? ($budget->spent_amount / $budget->allocated_amount) * 100 : 0; @endphp
                    <tr>
                        <td>{{ ucfirst($budget->category) }}
                            @if($budget->notes)<br><small class="text-muted">{{ $budget->notes }}</small>@endif
                        </td>
                        <td class="text-right">{{ number_format($budget->allocated_amount, 2) }}</td>
                        <td class="text-right">{{ number_format($budget->spent_amount, 2) }}</td>
                        <td class="text-right {{ $pct > 100 ? 'text-danger font-weight-bold' : '' }}">
                            {{ number_format($budget->allocated_amount - $budget->spent_amount, 2) }}
                        </td>
                        <td class="text-center">
                            <span class="badge badge-{{ $pct > 100 ? 'danger' : ($pct > 90 ? 'warning' : 'success') }}">
                                {{ number_format($pct, 0) }}%
                            </span>
                        </td>
                        @if($canManage)
                        <td>
                            <form action="{{ route('awards.budgets.destroy', [$award, $budget]) }}" method="POST"
                                  onsubmit="return confirm('Remove this budget line?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="font-weight-bold">
                    <tr>
                        <td>Total</td>
                        <td class="text-right">{{ number_format($totalAllocated, 2) }} {{ $award->currency }}</td>
                        <td class="text-right">{{ number_format($totalSpent, 2) }}</td>
                        <td class="text-right">{{ number_format($totalAllocated - $totalSpent, 2) }}</td>
                        <td></td>
                        @if($canManage)<td></td>@endif
                    </tr>
                </tfoot>
            </table>
            @else
            <div class="text-center text-muted py-3">No budget lines yet.</div>
            @endif
        </div>
    </div>

    {{-- ======== REPORTS ======== --}}
    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-invoice mr-1"></i>Reports ({{ $award->reports->count() }})</h3>
            <div class="card-tools">
                <a href="{{ route('reports.create') }}?award_id={{ $award->id }}" class="btn btn-xs btn-primary">
                    <i class="fas fa-plus"></i> Add Report
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            @if($award->reports->count() > 0)
            <table class="table table-sm mb-0">
                <thead class="thead-light">
                    <tr><th>Title</th><th>Type</th><th>Due</th><th>Submitted</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($award->reports->sortBy('due_date') as $report)
                    @php $overdue = $report->due_date->isPast() && $report->status === 'pending'; @endphp
                    <tr class="{{ $overdue ? 'table-danger' : '' }}">
                        <td>{{ Str::limit($report->title, 40) }}</td>
                        <td><span class="badge badge-secondary">{{ ucfirst($report->report_type) }}</span></td>
                        <td class="{{ $overdue ? 'font-weight-bold' : '' }}">{{ $report->due_date->format('d M Y') }}</td>
                        <td>{{ $report->submitted_date?->format('d M Y') ?? '-' }}</td>
                        <td>
                            @if($report->status === 'approved')     <span class="badge badge-success">Approved</span>
                            @elseif($report->status === 'submitted') <span class="badge badge-info">Submitted</span>
                            @elseif($report->status === 'under_review') <span class="badge badge-warning">Under Review</span>
                            @elseif($report->status === 'rejected') <span class="badge badge-danger">Rejected</span>
                            @else                                   <span class="badge badge-secondary">{{ $overdue ? 'Overdue' : 'Pending' }}</span>
                            @endif
                        </td>
                        <td><a href="{{ route('reports.show', $report) }}" class="btn btn-xs btn-outline-primary">View</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="text-center text-muted py-3">No reports yet. <a href="{{ route('reports.create') }}?award_id={{ $award->id }}">Add the first one.</a></div>
            @endif
        </div>
    </div>

    {{-- ======== COMPLIANCE CALENDAR ======== --}}
    <div class="card card-outline card-secondary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-calendar-check mr-1"></i>Compliance Calendar ({{ $award->complianceCalendar->count() }})</h3>
            <div class="card-tools">
                @if($canManage)
                <button class="btn btn-xs btn-primary" data-toggle="collapse" data-target="#addComplianceForm">
                    <i class="fas fa-plus"></i> Add Event
                </button>
                @endif
            </div>
        </div>
        <div class="card-body p-0">

            @if($canManage)
            <div class="collapse" id="addComplianceForm">
                <div class="p-3 bg-light border-bottom">
                    <form action="{{ route('awards.compliance.store', $award) }}" method="POST">
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label class="small">Event Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control form-control-sm"
                                       placeholder="e.g. Year 1 Technical Report">
                            </div>
                            <div class="col-md-3">
                                <label class="small">Deliverable Type <span class="text-danger">*</span></label>
                                <select name="deliverable_type" class="form-control form-control-sm">
                                    <option value="technical_report">Technical Report</option>
                                    <option value="financial_report">Financial Report</option>
                                    <option value="ethics_renewal">Ethics Renewal</option>
                                    <option value="audit">Audit</option>
                                    <option value="site_visit">Site Visit</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="small">Due Date <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm btn-block">Add</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            @if($award->complianceCalendar->count() > 0)
            <table class="table table-sm mb-0">
                <thead class="thead-light">
                    <tr><th>Event</th><th>Type</th><th>Due Date</th><th>Status</th>@if($canManage)<th></th>@endif</tr>
                </thead>
                <tbody>
                    @foreach($award->complianceCalendar->sortBy('due_date') as $item)
                    @php $overdue = $item->due_date->isPast() && !in_array($item->status, ['submitted','waived']); @endphp
                    <tr class="{{ $overdue ? 'table-warning' : '' }}">
                        <td>{{ $item->title }}</td>
                        <td><small class="text-muted">{{ ucfirst(str_replace('_',' ',$item->deliverable_type)) }}</small></td>
                        <td class="{{ $overdue ? 'font-weight-bold text-danger' : '' }}">{{ $item->due_date->format('d M Y') }}</td>
                        <td>
                            @if($canManage)
                            <form action="{{ route('awards.compliance.update', [$award, $item]) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <select name="status" class="form-control form-control-sm d-inline-block" style="width:auto;"
                                        onchange="this.form.submit()">
                                    <option value="upcoming"  {{ $item->status === 'upcoming'  ? 'selected' : '' }}>Upcoming</option>
                                    <option value="submitted" {{ $item->status === 'submitted' ? 'selected' : '' }}>Submitted</option>
                                    <option value="overdue"   {{ $item->status === 'overdue'   ? 'selected' : '' }}>Overdue</option>
                                    <option value="waived"    {{ $item->status === 'waived'    ? 'selected' : '' }}>Waived</option>
                                </select>
                            </form>
                            @else
                            @if($item->status === 'submitted') <span class="badge badge-success">Submitted</span>
                            @elseif($item->status === 'waived') <span class="badge badge-secondary">Waived</span>
                            @elseif($item->status === 'overdue') <span class="badge badge-danger">Overdue</span>
                            @else <span class="badge badge-warning">Upcoming</span>
                            @endif
                            @endif
                        </td>
                        @if($canManage)
                        <td>
                            <form action="{{ route('awards.compliance.destroy', [$award, $item]) }}" method="POST"
                                  onsubmit="return confirm('Remove this event?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="text-center text-muted py-3">No compliance events yet.</div>
            @endif
        </div>
    </div>

    {{-- ======== ETHICS RENEWALS ======== --}}
    <div class="card card-outline card-danger">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-shield-alt mr-1"></i>Ethics / Protocol Approvals ({{ $award->ethicsRenewals->count() }})</h3>
            <div class="card-tools">
                @if($canManage)
                <button class="btn btn-xs btn-primary" data-toggle="collapse" data-target="#addEthicsForm">
                    <i class="fas fa-plus"></i> Add
                </button>
                @endif
            </div>
        </div>
        <div class="card-body p-0">

            @if($canManage)
            <div class="collapse" id="addEthicsForm">
                <div class="p-3 bg-light border-bottom">
                    <form action="{{ route('awards.ethics.store', $award) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label class="small">Ethics Number <span class="text-danger">*</span></label>
                                <input type="text" name="ethics_number" class="form-control form-control-sm"
                                       placeholder="e.g. NIMR/HQ/R.8a">
                            </div>
                            <div class="col-md-3">
                                <label class="small">Issuing Body</label>
                                <input type="text" name="issuing_body" class="form-control form-control-sm"
                                       placeholder="e.g. NIMR Ethics Committee">
                            </div>
                            <div class="col-md-2">
                                <label class="small">Approval Date</label>
                                <input type="date" name="approval_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="small">Expiry Date <span class="text-danger">*</span></label>
                                <input type="date" name="expiry_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="small">Status</label>
                                <select name="renewal_status" class="form-control form-control-sm">
                                    <option value="valid">Valid</option>
                                    <option value="renewal_due">Renewal Due</option>
                                    <option value="expired">Expired</option>
                                    <option value="renewed">Renewed</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-1 align-items-end">
                            <div class="col-md-4">
                                <label class="small">Certificate File</label>
                                <input type="file" name="certificate_file" class="form-control-file form-control-sm"
                                       accept=".pdf,.doc,.docx">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm btn-block">Add</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            @if($award->ethicsRenewals->count() > 0)
            <table class="table table-sm mb-0">
                <thead class="thead-light">
                    <tr><th>Ethics No.</th><th>Issuing Body</th><th>Approved</th><th>Expiry</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($award->ethicsRenewals->sortBy('expiry_date') as $renewal)
                    @php $expiring = \App\Support\Days::until($renewal->expiry_date) <= 60 && $renewal->renewal_status === 'valid'; @endphp
                    <tr class="{{ $renewal->renewal_status === 'expired' ? 'table-danger' : ($expiring ? 'table-warning' : '') }}">
                        <td><strong>{{ $renewal->ethics_number }}</strong></td>
                        <td>{{ $renewal->issuing_body ?? '-' }}</td>
                        <td>{{ $renewal->approval_date?->format('d M Y') ?? '-' }}</td>
                        <td>{{ $renewal->expiry_date->format('d M Y') }}
                            @if($expiring)<br><small class="text-warning">Expiring soon</small>@endif
                        </td>
                        <td>
                            @if($canManage)
                            <form action="{{ route('awards.ethics.update', [$award, $renewal]) }}" method="POST"
                                  enctype="multipart/form-data" class="d-inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="expiry_date" value="{{ $renewal->expiry_date->format('Y-m-d') }}">
                                <select name="renewal_status" class="form-control form-control-sm d-inline-block"
                                        style="width:auto;" onchange="this.form.submit()">
                                    <option value="valid"       {{ $renewal->renewal_status === 'valid'       ? 'selected' : '' }}>Valid</option>
                                    <option value="renewal_due" {{ $renewal->renewal_status === 'renewal_due' ? 'selected' : '' }}>Renewal Due</option>
                                    <option value="expired"     {{ $renewal->renewal_status === 'expired'     ? 'selected' : '' }}>Expired</option>
                                    <option value="renewed"     {{ $renewal->renewal_status === 'renewed'     ? 'selected' : '' }}>Renewed</option>
                                </select>
                            </form>
                            @else
                            <span class="badge badge-{{ $renewal->renewal_status === 'valid' ? 'success' : ($renewal->renewal_status === 'expired' ? 'danger' : 'warning') }}">
                                {{ ucfirst(str_replace('_',' ',$renewal->renewal_status)) }}
                            </span>
                            @endif
                        </td>
                        <td>
                            @if($renewal->certificate_file)
                            <a href="{{ route('awards.ethics.certificate', [$award, $renewal]) }}" target="_blank"
                               class="btn btn-xs btn-outline-info" title="Download Certificate">
                                <i class="fas fa-download"></i>
                            </a>
                            @endif
                            @if($canManage)
                            <form action="{{ route('awards.ethics.destroy', [$award, $renewal]) }}" method="POST"
                                  class="d-inline" onsubmit="return confirm('Remove this ethics record?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="text-center text-muted py-3">No ethics records yet.</div>
            @endif
        </div>
    </div>

    {{-- ======== MONITORING VISITS ======== --}}
    <div class="card card-outline card-dark">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-binoculars mr-1"></i>Monitoring Visits ({{ $award->monitoringVisits->count() }})</h3>
            <div class="card-tools">
                @if($canManage)
                <button class="btn btn-xs btn-primary" data-toggle="collapse" data-target="#addVisitForm">
                    <i class="fas fa-plus"></i> Log Visit
                </button>
                @endif
            </div>
        </div>
        <div class="card-body p-0">

            @if($canManage)
            <div class="collapse" id="addVisitForm">
                <div class="p-3 bg-light border-bottom">
                    <form action="{{ route('awards.monitoring.store', $award) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group mb-1">
                                    <label class="small">Visit Date <span class="text-danger">*</span></label>
                                    <input type="date" name="visit_date" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-1">
                                    <label class="small">Location</label>
                                    <input type="text" name="location" class="form-control form-control-sm"
                                           placeholder="e.g. Dar es Salaam office">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-1">
                                    <label class="small">Key Findings <span class="text-danger">*</span></label>
                                    <textarea name="findings" rows="2" class="form-control form-control-sm"
                                              placeholder="Summary of findings (min 20 characters)..."></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-1">
                                    <label class="small">Recommendations</label>
                                    <textarea name="recommendations" rows="2" class="form-control form-control-sm"
                                              placeholder="Follow-up actions required..."></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-1">
                                    <label class="small">Report File</label>
                                    <input type="file" name="report_file" class="form-control-file form-control-sm"
                                           accept=".pdf,.doc,.docx">
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-sm btn-block mb-1">Log Visit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            @if($award->monitoringVisits->count() > 0)
            @foreach($award->monitoringVisits->sortByDesc('visit_date') as $visit)
            <div class="p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div class="d-flex justify-content-between">
                    <div>
                        <strong><i class="fas fa-calendar mr-1"></i>{{ $visit->visit_date->format('d M Y') }}</strong>
                        @if($visit->location)
                            <span class="text-muted ml-2"><i class="fas fa-map-marker-alt mr-1"></i>{{ $visit->location }}</span>
                        @endif
                        <span class="text-muted ml-2 small">by {{ $visit->conductedBy->name }}</span>
                    </div>
                    <div>
                        @if($visit->report_file)
                        <a href="{{ route('awards.monitoring.report', [$award, $visit]) }}" target="_blank"
                           class="btn btn-xs btn-outline-info mr-1"><i class="fas fa-download"></i></a>
                        @endif
                        @if($canManage)
                        <form action="{{ route('awards.monitoring.destroy', [$award, $visit]) }}" method="POST"
                              class="d-inline" onsubmit="return confirm('Remove this visit record?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                        @endif
                    </div>
                </div>
                <p class="mb-1 mt-1"><strong>Findings:</strong> {{ $visit->findings }}</p>
                @if($visit->recommendations)
                <p class="mb-0 text-muted small"><strong>Recommendations:</strong> {{ $visit->recommendations }}</p>
                @endif
            </div>
            @endforeach
            @else
            <div class="text-center text-muted py-3">No monitoring visits recorded.</div>
            @endif
        </div>
    </div>

</div>

{{-- ============================================================ RIGHT COLUMN ============================================================ --}}
<div class="col-md-4">

    {{-- Traffic Light --}}
    <div class="card card-{{ $tlColor }} card-outline">
        <div class="card-header"><h3 class="card-title">Project Health</h3></div>
        <div class="card-body text-center">
            <i class="fas fa-circle fa-4x text-{{ $tlColor }} mb-2"></i>
            <h4 class="text-{{ $tlColor }}">{{ $tlLabel }}</h4>
            @php
                $overdueReports = $award->reports->where('status','pending')->filter(fn($r) => $r->due_date->isPast())->count();
                $upcomingItems  = $award->complianceCalendar->where('status','upcoming')
                    ->filter(fn($c) => $c->due_date->lte(now()->addDays(14)))->count();
                $expiringEthics = $award->ethicsRenewals->filter(fn($e) =>
                    \App\Support\Days::until($e->expiry_date) <= 60 && $e->renewal_status === 'valid'
                )->count();
            @endphp
            <ul class="list-unstyled text-left mt-3 small">
                <li class="{{ $overdueReports > 0 ? 'text-danger' : 'text-muted' }}">
                    <i class="fas fa-{{ $overdueReports > 0 ? 'times' : 'check' }}-circle mr-1"></i>
                    {{ $overdueReports }} overdue report(s)
                </li>
                <li class="{{ $upcomingItems > 0 ? 'text-warning' : 'text-muted' }}">
                    <i class="fas fa-{{ $upcomingItems > 0 ? 'exclamation' : 'check' }}-circle mr-1"></i>
                    {{ $upcomingItems }} compliance deadline(s) in 14 days
                </li>
                <li class="{{ $burnRate > 100 ? 'text-danger' : ($burnRate > 90 ? 'text-warning' : 'text-muted') }}">
                    <i class="fas fa-dollar-sign mr-1"></i>
                    {{ number_format($burnRate, 1) }}% budget utilised
                </li>
                <li class="{{ $expiringEthics > 0 ? 'text-warning' : 'text-muted' }}">
                    <i class="fas fa-{{ $expiringEthics > 0 ? 'exclamation' : 'check' }}-circle mr-1"></i>
                    {{ $expiringEthics }} ethics approval(s) expiring within 60 days
                </li>
            </ul>
        </div>
    </div>

    {{-- Quick Stats --}}
    {{-- Audit readiness — slide 12 of the GMU concept --}}
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-clipboard-check mr-1"></i>Audit Readiness</h3></div>
        <div class="card-body">
            @php
                $band = $readiness['band'];
                $bandColour = match($band) {
                    'ready' => 'success',
                    'minor gaps' => 'info',
                    'significant gaps' => 'warning',
                    default => 'danger',
                };
            @endphp
            <div class="d-flex align-items-baseline justify-content-between mb-2">
                <span class="display-4 text-{{ $bandColour }}">{{ $readiness['score'] }}</span>
                <span class="badge badge-{{ $bandColour }}">{{ ucfirst($band) }}</span>
            </div>
            <div class="progress mb-3" style="height:6px">
                <div class="progress-bar bg-{{ $bandColour }}" style="width: {{ $readiness['score'] }}%"></div>
            </div>

            @foreach($readiness['dimensions'] as $dimension)
                <div class="d-flex justify-content-between small">
                    <span class="text-muted">{{ $dimension['label'] }}</span>
                    <span>{{ $dimension['earned'] }} / {{ $dimension['weight'] }}</span>
                </div>
            @endforeach

            @if(count($readiness['gaps']) > 0)
                <hr class="my-2">
                <p class="small text-muted mb-1">What an auditor would ask about:</p>
                <ul class="small pl-3 mb-0">
                    @foreach($readiness['gaps'] as $gap)
                        <li>{{ $gap }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Closeout — GM-BPF workflow D --}}
    <div class="card card-outline card-success">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-flag-checkered mr-1"></i>Closeout</h3></div>
        <div class="card-body">
            @php $closeout = $award->closeout; @endphp
            @if($closeout && $closeout->status === 'completed')
                <p class="small mb-2"><span class="badge badge-success">Closed out</span>
                    {{ $closeout->completed_at?->format('j M Y') }}</p>
            @elseif($closeout && $closeout->progress() > 0)
                <div class="progress mb-2" style="height:6px">
                    <div class="progress-bar bg-success" style="width: {{ $closeout->progress() }}%"></div>
                </div>
                <p class="small text-muted mb-2">{{ $closeout->progress() }}% complete,
                    {{ count($closeout->outstandingSteps()) }} step(s) outstanding.</p>
            @elseif(\App\Support\Days::until($award->end_date) <= 60)
                <p class="small text-muted mb-2">
                    The award ends {{ \App\Support\Days::describe($award->end_date) }}. Closeout should begin now.
                </p>
            @else
                <p class="small text-muted mb-2">Not yet started — the award is still running.</p>
            @endif
            <a href="{{ route('awards.closeout', $award) }}" class="btn btn-sm btn-outline-success btn-block">
                Open closeout checklist
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Quick Stats</h3></div>
        <div class="card-body p-0">
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span>Total Budget</span>
                    <strong>{{ number_format($award->total_budget, 0) }} {{ $award->currency }}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Allocated in Lines</span>
                    <strong>{{ number_format($totalAllocated, 0) }}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Total Spent</span>
                    <strong>{{ number_format($totalSpent, 0) }}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Reports Approved</span>
                    <strong>{{ $award->reports->where('status','approved')->count() }} / {{ $award->reports->count() }}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Compliance Events</span>
                    <strong>{{ $award->complianceCalendar->where('status','submitted')->count() }} / {{ $award->complianceCalendar->count() }} done</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Monitoring Visits</span>
                    <strong>{{ $award->monitoringVisits->count() }}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Ethics Approvals</span>
                    <strong>{{ $award->ethicsRenewals->count() }}</strong>
                </li>
            </ul>
        </div>
    </div>

    {{-- Amendments and no-cost extensions --}}
    <div class="card card-outline card-secondary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title"><i class="fas fa-file-signature mr-1"></i>Amendments ({{ $award->amendments->count() }})</h3>
            @can('update', $award)
            <button class="btn btn-xs btn-secondary" data-toggle="collapse" data-target="#addAmendment">
                <i class="fas fa-plus mr-1"></i>Record
            </button>
            @endcan
        </div>

        @can('update', $award)
        <div class="collapse" id="addAmendment">
            <div class="card-body bg-light border-bottom">
                <form method="POST" action="{{ route('awards.amendments.store', $award) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label class="small text-uppercase text-muted">Type</label>
                            <select name="type" class="form-control form-control-sm">
                                @foreach(\App\Models\AwardAmendment::TYPES as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small text-uppercase text-muted">New end date</label>
                            <input type="date" name="new_end_date" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small text-uppercase text-muted">New value ({{ $award->currency }})</label>
                            <input type="number" step="0.01" min="0" name="new_budget" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small text-uppercase text-muted">Donor reference</label>
                            <input type="text" name="reference" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="small text-uppercase text-muted">Reason</label>
                            <input type="text" name="reason" class="form-control form-control-sm" required
                                   placeholder="Why the change was sought">
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-uppercase text-muted">Status</label>
                            <select name="status" class="form-control form-control-sm">
                                <option value="draft">Draft</option>
                                <option value="submitted">Submitted</option>
                                <option value="approved">Approved by donor</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-uppercase text-muted">Approved on</label>
                            <input type="date" name="donor_approved_on" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-uppercase text-muted">Approval letter</label>
                            <input type="file" name="approval_file" class="form-control-file form-control-sm">
                        </div>
                    </div>
                    <p class="small text-muted mb-2">
                        Recording an approved amendment applies it to the award, and an extension
                        regenerates the reporting calendar so deadlines follow the new period.
                    </p>
                    <button class="btn btn-sm btn-secondary">Record amendment</button>
                </form>
            </div>
        </div>
        @endcan

        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="thead-light">
                    <tr><th>Type</th><th>Reason</th><th>Effect</th><th>Status</th><th>Approved</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($award->amendments as $amendment)
                    <tr>
                        <td><strong>{{ $amendment->typeName() }}</strong>
                            @if($amendment->reference)<br><small class="text-muted">{{ $amendment->reference }}</small>@endif
                        </td>
                        <td><small>{{ Str::limit($amendment->reason, 60) }}</small></td>
                        <td>
                            @php $days = $amendment->daysExtended(); $delta = $amendment->budgetDelta(); @endphp
                            @if($days)<small>{{ $days > 0 ? '+' : '' }}{{ $days }} days</small><br>@endif
                            @if($delta !== null && $delta != 0)
                                <small>{{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 0) }} {{ $award->currency }}</small>
                            @endif
                            @if(! $days && ($delta === null || $delta == 0))<small class="text-muted">—</small>@endif
                        </td>
                        <td><span class="badge badge-{{ $amendment->status === 'approved' ? 'success' : ($amendment->status === 'rejected' ? 'danger' : 'secondary') }}">
                            {{ ucfirst($amendment->status) }}</span></td>
                        <td><small>{{ $amendment->donor_approved_on?->format('j M Y') ?? '—' }}</small></td>
                        <td class="text-right">
                            @if($amendment->approval_file)
                                <a href="{{ route('awards.amendments.approval', [$award, $amendment]) }}" class="btn btn-xs btn-outline-secondary">
                                    <i class="fas fa-file"></i>
                                </a>
                            @endif
                            @can('update', $award)
                            <form method="POST" action="{{ route('awards.amendments.destroy', [$award, $amendment]) }}" class="d-inline"
                                  onsubmit="return confirm('Remove this amendment record?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">
                        No amendments. Extensions, budget revisions and scope changes belong here so the award shows its history, not just its current state.
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Sub-awards — slide 12, and the sector weakness the benchmarking found --}}
    <div class="card card-outline card-info">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title"><i class="fas fa-handshake mr-1"></i>Sub-awards ({{ $award->subAwards->count() }})</h3>
            @can('manageSubAwards', $award)
            <button class="btn btn-xs btn-info" data-toggle="collapse" data-target="#addSubAward">
                <i class="fas fa-plus mr-1"></i>Add
            </button>
            @endcan
        </div>

        @can('manageSubAwards', $award)
        <div class="collapse" id="addSubAward">
            <div class="card-body bg-light border-bottom">
                <form method="POST" action="{{ route('awards.sub-awards.store', $award) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label class="small text-uppercase text-muted">Partner institution</label>
                            <input type="text" name="partner_name" class="form-control form-control-sm" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small text-uppercase text-muted">Type</label>
                            <select name="partner_type" class="form-control form-control-sm">
                                @foreach(\App\Models\SubAward::PARTNER_TYPES as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-uppercase text-muted">Country</label>
                            <input type="text" name="country" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-uppercase text-muted">Amount ({{ $award->currency }})</label>
                            <input type="number" step="0.01" min="0" name="amount" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label class="small text-uppercase text-muted">Start</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small text-uppercase text-muted">End</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-uppercase text-muted">Status</label>
                            <select name="status" class="form-control form-control-sm">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-uppercase text-muted">Reporting</label>
                            <select name="reporting_frequency" class="form-control form-control-sm">
                                <option value="quarterly">Quarterly</option>
                                <option value="monthly">Monthly</option>
                                <option value="semi_annual">Every 6 months</option>
                                <option value="annual">Annually</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-uppercase text-muted">Due diligence</label>
                            <input type="date" name="due_diligence_completed_on" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label class="small text-uppercase text-muted">Signed agreement</label>
                            <input type="file" name="agreement_file" class="form-control-file form-control-sm">
                        </div>
                        <div class="form-group col-md-4 d-flex align-items-end">
                            <button class="btn btn-sm btn-info btn-block">Record sub-award</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endcan

        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="thead-light">
                    <tr><th></th><th>Partner</th><th>Amount</th><th>Disbursed</th><th>Period</th><th>Status</th><th>Due diligence</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($award->subAwards as $sub)
                    <tr>
                        <td><span class="traffic-dot traffic-{{ $sub->health() }}" title="Sub-award health"></span></td>
                        <td>
                            <strong>{{ $sub->partner_name }}</strong>
                            <br><small class="text-muted">{{ \App\Models\SubAward::PARTNER_TYPES[$sub->partner_type] ?? $sub->partner_type }}@if($sub->country) · {{ $sub->country }}@endif</small>
                        </td>
                        <td>{{ $sub->currency }} {{ number_format($sub->amount, 0) }}</td>
                        <td>{{ number_format($sub->disbursedPercentage(), 0) }}%</td>
                        <td><small>{{ $sub->start_date?->format('M Y') }} → {{ $sub->end_date?->format('M Y') }}</small></td>
                        <td><span class="badge badge-{{ $sub->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($sub->status) }}</span></td>
                        <td>
                            @if($sub->due_diligence_completed_on)
                                <small class="text-success">{{ $sub->due_diligence_completed_on->format('M Y') }}</small>
                            @else
                                <small class="text-danger">Not done</small>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($sub->agreement_file)
                                <a href="{{ route('awards.sub-awards.agreement', [$award, $sub]) }}" class="btn btn-xs btn-outline-secondary" title="Signed agreement">
                                    <i class="fas fa-file-contract"></i>
                                </a>
                            @endif
                            @can('manageSubAwards', $award)
                            <form method="POST" action="{{ route('awards.sub-awards.destroy', [$award, $sub]) }}" class="d-inline"
                                  onsubmit="return confirm('Remove this sub-award?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-3">
                        No sub-awards recorded. Money passed to a partner institution belongs here — NIMR stays accountable to the donor for it.
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Expenditure ledger --}}
    <div class="card card-outline card-warning">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title"><i class="fas fa-receipt mr-1"></i>Expenditure Ledger ({{ $award->expenditures->count() }})</h3>
            @can('manageBudget', $award)
            <button class="btn btn-xs btn-warning" data-toggle="collapse" data-target="#addExpenditure">
                <i class="fas fa-plus mr-1"></i>Record
            </button>
            @endcan
        </div>

        @can('manageBudget', $award)
        <div class="collapse" id="addExpenditure">
            <div class="card-body bg-light border-bottom">
                <form method="POST" action="{{ route('awards.expenditures.store', $award) }}">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label class="small text-uppercase text-muted">Date</label>
                            <input type="date" name="transaction_date" class="form-control form-control-sm" required
                                   max="{{ now()->toDateString() }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small text-uppercase text-muted">Budget line</label>
                            <select name="award_budget_id" class="form-control form-control-sm">
                                <option value="">Unassigned</option>
                                @foreach($award->budgets as $line)
                                    <option value="{{ $line->id }}">{{ ucfirst($line->category) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="small text-uppercase text-muted">Description</label>
                            <input type="text" name="description" class="form-control form-control-sm" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-uppercase text-muted">Amount</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="small text-uppercase text-muted">Voucher / invoice reference</label>
                            <input type="text" name="reference" class="form-control form-control-sm"
                                   placeholder="Traceable to the finance record">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="small text-uppercase text-muted">Notes</label>
                            <input type="text" name="notes" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-2 d-flex align-items-end">
                            <button class="btn btn-sm btn-warning btn-block">Post</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endcan

        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="thead-light">
                    <tr><th>Date</th><th>Description</th><th>Budget line</th><th>Reference</th><th class="text-right">Amount</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($award->expenditures->sortByDesc('transaction_date')->take(15) as $entry)
                    <tr>
                        <td><small>{{ $entry->transaction_date?->format('j M Y') }}</small></td>
                        <td>{{ $entry->description }}
                            @if($entry->recordedBy)<br><small class="text-muted">by {{ $entry->recordedBy->name }}</small>@endif
                        </td>
                        <td><small>{{ $entry->budgetLine ? ucfirst($entry->budgetLine->category) : '—' }}</small></td>
                        <td><small class="{{ $entry->reference ? '' : 'text-danger' }}">{{ $entry->reference ?: 'none' }}</small></td>
                        <td class="text-right">{{ $entry->currency }} {{ number_format($entry->amount, 2) }}</td>
                        <td class="text-right">
                            @can('manageBudget', $award)
                            <form method="POST" action="{{ route('awards.expenditures.destroy', [$award, $entry]) }}"
                                  onsubmit="return confirm('Remove this entry?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs btn-outline-danger"><i class="fas fa-times"></i></button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">
                        No transactions recorded. Budget lines show what was spent; this is what an auditor follows it back to.
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($award->expenditures->count() > 15)
                <div class="card-footer text-muted small">Showing the 15 most recent of {{ $award->expenditures->count() }} entries.</div>
            @endif
        </div>
    </div>

    {{-- Linked Proposal + Award Info --}}
    <div class="card bg-light">
        <div class="card-body">
            <p class="small text-muted mb-1"><i class="fas fa-calendar-alt mr-1"></i>
                <strong>{{ $award->start_date->format('M Y') }}</strong> → <strong>{{ $award->end_date->format('M Y') }}</strong>
                ({{ $award->start_date->diffInMonths($award->end_date) }} months)
            </p>
            @if($award->kickoff_date)
            <p class="small text-muted mb-1"><i class="fas fa-flag mr-1"></i>
                Kickoff: <strong>{{ $award->kickoff_date->format('d M Y') }}</strong>
            </p>
            @endif
            <p class="small text-muted mb-0"><i class="fas fa-clock mr-1"></i>
                Days remaining: <strong>{{ max(0, \App\Support\Days::until($award->end_date)) }}</strong>
            </p>
        </div>
    </div>

</div>
</div>

@endsection
