@extends('adminlte::page')

@section('title', 'My Grants')

@section('content_header')
    <h1 class="m-0">My Grants
        <small class="text-muted d-block" style="font-size:14px">
            What you owe, what is at risk, and what is open to apply for
        </small>
    </h1>
@stop

@section('content')

{{-- What needs attention, in numbers --}}
<div class="row">
    @php
        $tiles = [
            ['label' => 'My Active Awards',  'value' => $stats['my_awards'],       'icon' => 'fas fa-trophy',            'colour' => 'success'],
            ['label' => 'Due Within 30 Days','value' => $stats['reports_due'],     'icon' => 'fas fa-file-invoice',      'colour' => $stats['reports_due'] > 0 ? 'warning' : 'secondary'],
            ['label' => 'Overdue',           'value' => $stats['overdue'],         'icon' => 'fas fa-exclamation-circle','colour' => $stats['overdue'] > 0 ? 'danger' : 'secondary'],
            ['label' => 'Compliance Flags',  'value' => $stats['high_risk_flags'], 'icon' => 'fas fa-flag',              'colour' => $stats['high_risk_flags'] > 0 ? 'danger' : 'success'],
            ['label' => 'My Proposals',      'value' => $stats['my_proposals'],    'icon' => 'fas fa-file-alt',          'colour' => 'info'],
            ['label' => 'Open Calls',        'value' => $stats['open_calls'],      'icon' => 'fas fa-bullhorn',          'colour' => 'primary'],
        ];
    @endphp

    @foreach($tiles as $tile)
    <div class="col-md-2 col-sm-4 col-6">
        <div class="small-box bg-{{ $tile['colour'] }}">
            <div class="inner">
                <h3>{{ $tile['value'] }}</h3>
                <p class="mb-0" style="font-size:13px">{{ $tile['label'] }}</p>
            </div>
            <div class="icon"><i class="{{ $tile['icon'] }}"></i></div>
        </div>
    </div>
    @endforeach
</div>

<div class="row">
    <div class="col-md-7">

        {{-- Deliverables: the reports and obligations this researcher owes --}}
        <div class="card card-outline card-warning">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-tasks mr-1"></i>My Deliverables</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Deliverable</th><th>Grant</th><th>Due</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($myReports as $report)
                            @php $days = \App\Support\Days::until($report->due_date); @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('reports.show', $report) }}">{{ $report->title }}</a>
                                    <br><small class="text-muted">{{ ucfirst($report->report_type) }} report</small>
                                </td>
                                <td><small>{{ Str::limit($report->award?->title, 34) }}</small></td>
                                <td>
                                    <small>{{ $report->due_date?->format('j M Y') }}</small>
                                    <br>
                                    <span class="badge badge-{{ $days < 0 ? 'danger' : ($days <= 14 ? 'warning' : 'secondary') }}">
                                        {{ $days < 0 ? abs($days) . ' days overdue' : \App\Support\Days::describe($report->due_date) }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('reports.edit', $report) }}" class="btn btn-xs btn-outline-primary">Submit</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">Nothing outstanding — your reporting is up to date.</td></tr>
                        @endforelse

                        @foreach($myDeliverables as $item)
                            @php $days = \App\Support\Days::until($item->due_date); @endphp
                            <tr>
                                <td>{{ $item->title }}<br><small class="text-muted">{{ str_replace('_', ' ', $item->deliverable_type) }}</small></td>
                                <td><small>{{ Str::limit($item->award?->title, 34) }}</small></td>
                                <td>
                                    <small>{{ $item->due_date?->format('j M Y') }}</small>
                                    <br>
                                    <span class="badge badge-{{ $days < 0 ? 'danger' : ($days <= 14 ? 'warning' : 'secondary') }}">
                                        {{ $days < 0 ? abs($days) . ' days overdue' : \App\Support\Days::describe($item->due_date) }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('awards.show', $item->award_id) }}" class="btn btn-xs btn-outline-secondary">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- My grants, with the same traffic light the GMU sees --}}
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-trophy mr-1"></i>My Grants</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th></th><th>Grant</th><th>Donor</th><th>Ends</th><th>Budget used</th></tr>
                    </thead>
                    <tbody>
                        @forelse($activeAwards as $award)
                        <tr>
                            <td><span class="traffic-dot traffic-{{ $award->traffic_light }}"></span></td>
                            <td><a href="{{ route('awards.show', $award) }}">{{ Str::limit($award->title, 40) }}</a>
                                <br><small class="text-muted">{{ $award->contract_number }}</small></td>
                            <td><small>{{ $award->donor?->acronym ?? $award->donor?->name }}</small></td>
                            <td><small>{{ $award->end_date?->format('M Y') }}</small></td>
                            <td style="min-width:120px">
                                @php $burn = $award->burnRate(); @endphp
                                <div class="progress" style="height:6px">
                                    <div class="progress-bar bg-{{ $burn > 100 ? 'danger' : ($burn > 85 ? 'warning' : 'success') }}"
                                         style="width: {{ min(100, $burn) }}%"></div>
                                </div>
                                <small class="text-muted">{{ $burn }}%</small>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">
                            You are not currently on an active award. Open calls are listed on the right.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="col-md-5">

        {{-- Compliance flags — the same engine the GMU reads --}}
        <div class="card card-outline card-danger">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-flag mr-1"></i>Compliance Flags</h3></div>
            <div class="card-body p-0">
                @forelse(collect($flags)->sortBy(fn($f) => ['high' => 0, 'medium' => 1, 'low' => 2][$f['level']])->take(8) as $flag)
                    <div class="px-3 py-2 border-bottom">
                        <span class="badge badge-{{ $flag['level'] === 'high' ? 'danger' : ($flag['level'] === 'medium' ? 'warning' : 'secondary') }}">
                            {{ strtoupper($flag['level']) }}
                        </span>
                        <span class="ml-1">{{ $flag['message'] }}</span>
                        <div class="small text-muted mt-1">
                            {{ $flag['category'] }} · <a href="{{ route('awards.show', $flag['award_id']) }}">{{ Str::limit($flag['award'], 40) }}</a>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-muted py-3 mb-0">
                        <i class="fas fa-check-circle text-success mr-1"></i>Nothing flagged on your grants.
                    </p>
                @endforelse
            </div>
        </div>

        {{-- Calls this researcher could apply for --}}
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-bullhorn mr-1"></i>Open Calls</h3></div>
            <div class="card-body p-0">
                @forelse($recentCalls as $call)
                    @php $days = $call->days_until_deadline; @endphp
                    <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('calls.show', $call) }}">{{ Str::limit($call->title, 44) }}</a>
                            <div class="small text-muted">{{ $call->donor?->name }}</div>
                        </div>
                        <span class="badge badge-{{ $days <= 7 ? 'danger' : ($days <= 14 ? 'warning' : 'info') }}">
                            {{ $days }}d
                        </span>
                    </div>
                @empty
                    <p class="text-center text-muted py-3 mb-0">No open calls at the moment.</p>
                @endforelse
            </div>
        </div>

    </div>
</div>
@stop
