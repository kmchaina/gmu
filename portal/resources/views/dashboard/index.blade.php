@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0"><i class="fas fa-tachometer-alt mr-2"></i>Dashboard</h1>
        <small class="text-muted">{{ now()->format('l, d F Y') }}</small>
    </div>
@endsection

@section('content')

{{-- Stat Cards --}}
<div class="row">
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['open_calls'] }}</h3>
                <p>Open Funding Calls</p>
            </div>
            <div class="icon"><i class="fas fa-bullhorn"></i></div>
            <a href="{{ route('calls.index') }}" class="small-box-footer">View All <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['calls_closing_soon'] }}</h3>
                <p>Closing in 30 Days</p>
            </div>
            <div class="icon"><i class="fas fa-hourglass-half"></i></div>
            <a href="{{ route('calls.index') }}?filter=closing_soon" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $stats['active_proposals'] }}</h3>
                <p>Active Proposals</p>
            </div>
            <div class="icon"><i class="fas fa-file-alt"></i></div>
            <a href="{{ route('proposals.index') }}" class="small-box-footer">Pipeline <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['active_awards'] }}</h3>
                <p>Active Awards</p>
            </div>
            <div class="icon"><i class="fas fa-trophy"></i></div>
            <a href="{{ route('awards.index') }}" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="small-box bg-orange">
            <div class="inner">
                <h3>{{ $stats['reports_due_soon'] }}</h3>
                <p>Reports Due (14d)</p>
            </div>
            <div class="icon"><i class="fas fa-file-invoice"></i></div>
            <a href="{{ route('reports.index') }}" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stats['overdue_reports'] }}</h3>
                <p>Overdue Reports</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            <a href="{{ route('reports.index') }}?filter=overdue" class="small-box-footer">Urgent <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    {{-- Funding Calls Closing Soon --}}
    <div class="col-md-6">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bullhorn mr-1"></i> Funding Calls Closing Soon</h3>
                <div class="card-tools">
                    <a href="{{ route('calls.index') }}" class="btn btn-sm btn-primary">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                @if($recentCalls->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i><br>No open calls at the moment
                    </div>
                @else
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Call Title</th>
                                <th>Donor</th>
                                <th>Deadline</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentCalls as $call)
                            @php $days = $call->days_until_deadline; @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('calls.show', $call) }}" class="text-dark font-weight-bold">
                                        {{ Str::limit($call->title, 38) }}
                                    </a>
                                </td>
                                <td><span class="badge badge-secondary">{{ $call->donor->acronym ?? $call->donor->name }}</span></td>
                                <td>
                                    @if($days <= 7)
                                        <span class="badge badge-danger">{{ $call->deadline->format('d M Y') }} &bull; {{ $days }}d</span>
                                    @elseif($days <= 14)
                                        <span class="badge badge-warning">{{ $call->deadline->format('d M Y') }} &bull; {{ $days }}d</span>
                                    @else
                                        <span class="badge badge-info">{{ $call->deadline->format('d M Y') }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    {{-- Upcoming Compliance Deadlines --}}
    <div class="col-md-6">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-check mr-1"></i> Upcoming Compliance Deadlines</h3>
            </div>
            <div class="card-body p-0">
                @if($upcomingDeadlines->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fa-2x mb-2 text-success"></i><br>No deadlines in the next 30 days
                    </div>
                @else
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr><th>Deliverable</th><th>Award</th><th>Due</th></tr>
                        </thead>
                        <tbody>
                            @foreach($upcomingDeadlines as $item)
                            @php $daysLeft = \App\Support\Days::until($item->due_date); @endphp
                            <tr>
                                <td>{{ Str::limit($item->title, 30) }}<br>
                                    <small class="text-muted">{{ ucfirst(str_replace('_',' ',$item->deliverable_type)) }}</small>
                                </td>
                                <td><small>{{ Str::limit($item->award->title ?? '', 25) }}</small></td>
                                <td>
                                    @if($daysLeft <= 7)
                                        <span class="badge badge-danger">{{ $daysLeft }}d</span>
                                    @elseif($daysLeft <= 14)
                                        <span class="badge badge-warning">{{ $daysLeft }}d</span>
                                    @else
                                        <span class="badge badge-info">{{ $daysLeft }}d</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Active Awards --}}
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-trophy mr-1"></i> Active Awards</h3>
                <div class="card-tools">
                    <a href="{{ route('awards.index') }}" class="btn btn-sm btn-success">View All Awards</a>
                </div>
            </div>
            <div class="card-body p-0">
                @if($activeAwards->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-trophy fa-2x mb-2"></i><br>No active awards yet
                    </div>
                @else
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th width="40">Status</th>
                                <th>Award Title</th>
                                <th>Donor</th>
                                <th>Lead PI</th>
                                <th>End Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeAwards as $award)
                            <tr>
                                <td class="text-center">
                                    @php $tl = $award->traffic_light; @endphp
                                    <i class="fas fa-circle text-{{ $tl === 'green' ? 'success' : ($tl === 'yellow' ? 'warning' : 'danger') }}"
                                       title="{{ ucfirst($tl) }}"></i>
                                </td>
                                <td><strong>{{ Str::limit($award->title, 40) }}</strong></td>
                                <td><span class="badge badge-secondary">{{ $award->donor->acronym ?? $award->donor->name }}</span></td>
                                <td>{{ $award->proposal->leadPi->name ?? 'N/A' }}</td>
                                <td>{{ $award->end_date->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('awards.show', $award) }}" class="btn btn-xs btn-outline-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@section('css')
<style>
    .bg-orange { background-color: #fd7e14 !important; }
    .small-box .icon { font-size: 70px; top: 10px; }
</style>
@endsection
