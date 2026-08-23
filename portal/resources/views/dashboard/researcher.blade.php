@extends('layouts.gmu')

@section('title', 'My grants')
@section('crumb', 'Dashboard')

@section('content')
@php use App\Support\Days; @endphp

<x-ui.page-head
    title="My grants"
    :lede="now()->format('l, j F Y') . ' · what you owe, what is at risk, and what is open to apply for'">
    <x-slot:actions>
        <a href="{{ route('calls.index') }}" class="btn">Browse calls</a>
        @can('create', App\Models\Proposal::class)
            <a href="{{ route('proposals.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Register a proposal</a>
        @endcan
    </x-slot:actions>
</x-ui.page-head>

<div class="stack">

    <div class="stats">
        <x-ui.stat label="My active awards" :value="$stats['my_awards']" note="Grants you are on" />
        <x-ui.stat label="Due within 30 days" :value="$stats['reports_due']"
                   :tone="$stats['reports_due'] > 0 ? 'warn' : null" note="Reports and deliverables" />
        <x-ui.stat label="Overdue" :value="$stats['overdue']"
                   :tone="$stats['overdue'] > 0 ? 'danger' : 'ok'"
                   :note="$stats['overdue'] > 0 ? 'Submit these first' : 'Nothing late'" />
        <x-ui.stat label="Compliance flags" :value="$stats['high_risk_flags']"
                   :tone="$stats['high_risk_flags'] > 0 ? 'danger' : 'ok'" note="High severity, on your grants" />
        <x-ui.stat label="My proposals" :value="$stats['my_proposals']" note="In the pipeline" />
        <x-ui.stat label="Open calls" :value="$stats['open_calls']" note="Across all donors" />
    </div>

    <div class="grid grid-wide">
        <div class="stack">

            <x-ui.card title="My deliverables" note="Reports and obligations with your name on them" :flush="true">
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Deliverable</th><th>Grant</th><th>Due</th><th></th></tr></thead>
                        <tbody>
                            @forelse($myReports as $report)
                                @php $days = Days::until($report->due_date); @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('reports.show', $report) }}" class="strong">{{ $report->title }}</a>
                                        <div class="tiny muted-3">{{ ucfirst($report->report_type) }} report</div>
                                    </td>
                                    <td class="small muted">{{ Str::limit($report->award?->title, 30) }}</td>
                                    <td>
                                        <div class="small">{{ $report->due_date?->format('d M Y') }}</div>
                                        <x-ui.pill :tone="$days < 0 ? 'danger' : ($days <= 14 ? 'warn' : null)">
                                            {{ $days < 0 ? abs($days) . 'd overdue' : Days::describe($report->due_date) }}
                                        </x-ui.pill>
                                    </td>
                                    <td class="num"><a href="{{ route('reports.edit', $report) }}" class="btn btn-sm">Submit</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="4">
                                    <x-ui.empty icon="fa-check-circle" title="Nothing outstanding">
                                        Your reporting is up to date.
                                    </x-ui.empty>
                                </td></tr>
                            @endforelse

                            @foreach($myDeliverables as $item)
                                @php $days = Days::until($item->due_date); @endphp
                                <tr>
                                    <td>
                                        <span class="strong">{{ $item->title }}</span>
                                        <div class="tiny muted-3">{{ Str::headline($item->deliverable_type) }}</div>
                                    </td>
                                    <td class="small muted">{{ Str::limit($item->award?->title, 30) }}</td>
                                    <td>
                                        <div class="small">{{ $item->due_date?->format('d M Y') }}</div>
                                        <x-ui.pill :tone="$days < 0 ? 'danger' : ($days <= 14 ? 'warn' : null)">
                                            {{ $days < 0 ? abs($days) . 'd overdue' : Days::describe($item->due_date) }}
                                        </x-ui.pill>
                                    </td>
                                    <td class="num"><a href="{{ route('awards.show', $item->award_id) }}" class="btn btn-sm">Open</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            <x-ui.card title="My grants" :flush="true">
                <div class="list">
                    @forelse($activeAwards as $award)
                        @php $burn = $award->burnRate(); @endphp
                        <a href="{{ route('awards.show', $award) }}" class="list-row">
                            <span class="dot dot-{{ $award->traffic_light === 'red' ? 'red' : ($award->traffic_light === 'yellow' ? 'amber' : 'green') }}"></span>
                            <div class="list-main">
                                <span class="list-title">{{ $award->title }}</span>
                                <span class="list-sub mono">{{ $award->contract_number }} · {{ $award->donor?->acronym ?? $award->donor?->name }}</span>
                            </div>
                            <div style="width:150px;"><x-ui.meter :value="$burn" label="Budget used" /></div>
                            <div style="width:110px; text-align:right;">
                                <div class="small" style="font-weight:600;">{{ $award->currency }} {{ number_format($award->total_budget, 0) }}</div>
                                <div class="tiny muted-3">Ends {{ $award->end_date?->format('M Y') }}</div>
                            </div>
                        </a>
                    @empty
                        <x-ui.empty icon="fa-trophy" title="No active grants">
                            When a proposal you lead is funded and the contract is recorded, the grant appears here with
                            its deadlines and budget.
                        </x-ui.empty>
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        <div class="stack">
            <x-ui.card title="Compliance flags" note="From the same engine the GMU reads" :flush="true">
                <div class="list">
                    @forelse(collect($flags)->sortBy(fn($f) => ['high' => 0, 'medium' => 1, 'low' => 2][$f['level']])->take(8) as $flag)
                        <div class="list-row" style="align-items:flex-start;">
                            <div class="list-main">
                                <span class="list-title">{{ $flag['message'] }}</span>
                                <span class="list-sub">
                                    {{ $flag['category'] }} ·
                                    <a href="{{ route('awards.show', $flag['award_id']) }}">{{ Str::limit($flag['award'], 34) }}</a>
                                </span>
                            </div>
                            <x-ui.pill :tone="$flag['level'] === 'high' ? 'danger' : ($flag['level'] === 'medium' ? 'warn' : null)">
                                {{ strtoupper($flag['level']) }}
                            </x-ui.pill>
                        </div>
                    @empty
                        <x-ui.empty icon="fa-check-circle" title="Nothing flagged">
                            No compliance issues on your grants.
                        </x-ui.empty>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Open calls" note="Register interest before the deadline" :flush="true">
                <div class="list">
                    @forelse($recentCalls as $call)
                        @php $days = $call->days_until_deadline; @endphp
                        <a href="{{ route('calls.show', $call) }}" class="list-row">
                            <div class="list-main">
                                <span class="list-title">{{ Str::limit($call->title, 44) }}</span>
                                <span class="list-sub">{{ $call->donor?->acronym ?? $call->donor?->name }}</span>
                            </div>
                            <x-ui.pill :tone="$days <= 7 ? 'danger' : ($days <= 14 ? 'warn' : null)">{{ $days }}d</x-ui.pill>
                        </a>
                    @empty
                        <x-ui.empty icon="fa-bullhorn">No calls are open at the moment.</x-ui.empty>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    </div>
</div>
@endsection
