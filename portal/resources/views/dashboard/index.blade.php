@extends('layouts.gmu')

@section('title', 'Portfolio overview')
@section('crumb', 'Dashboard')

@section('content')
@php
    use App\Support\Days;
    use App\Support\Money;

    $closingSoonest = $recentCalls->first();
    $totalUnderManagement = \App\Models\Award::where('status', 'active')->sum('base_total_budget');
    $donorAcronyms = $activeAwards->map(fn($a) => $a->donor?->acronym ?? $a->donor?->name)->filter()->unique()->take(3)->implode(' · ');
@endphp

<x-ui.page-head
    title="Portfolio overview"
    :lede="now()->format('l, j F Y') . ' · ' . $stats['active_awards'] . ' active ' . Str::plural('award', $stats['active_awards']) . ' · ' . Money::base() . ' ' . number_format($totalUnderManagement) . ' under management'">
    <x-slot:actions>
        @can('view-analytics')
            <a href="{{ route('analytics') }}" class="btn"><i class="fas fa-chart-line"></i> Analytics</a>
        @endcan
        @can('create', App\Models\FundingCall::class)
            <a href="{{ route('calls.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Post a funding call</a>
        @endcan
    </x-slot:actions>
</x-ui.page-head>

<div class="stack">

    <div class="stats">
        <x-ui.stat label="Open funding calls" :value="$stats['open_calls']"
                   :note="$stats['calls_closing_soon'] . ' closing within 30 days'" />

        <x-ui.stat label="Closing in 30 days" :value="$stats['calls_closing_soon']"
                   :tone="$closingSoonest && $closingSoonest->days_until_deadline <= 14 ? 'warn' : null"
                   :note="$closingSoonest ? Str::limit($closingSoonest->title, 22) . ' in ' . $closingSoonest->days_until_deadline . 'd' : 'Nothing closing soon'" />

        <x-ui.stat label="Active proposals" :value="$stats['active_proposals']" note="In the pipeline now" />

        <x-ui.stat label="Active awards" :value="$stats['active_awards']" :note="$donorAcronyms ?: 'No active awards'" />

        <x-ui.stat label="Reports due in 14 days" :value="$stats['reports_due_soon']"
                   :tone="$stats['reports_due_soon'] > 0 ? 'warn' : null"
                   note="Technical and financial" />

        <x-ui.stat label="Overdue reports" :value="$stats['overdue_reports']"
                   :tone="$stats['overdue_reports'] > 0 ? 'danger' : 'ok'"
                   :note="$stats['overdue_reports'] > 0 ? 'Chase these today' : 'Nothing overdue'" />
    </div>

    <div class="grid grid-wide">

        <x-ui.card title="Active awards"
                   note="Risk level from the compliance engine: reports, ethics, calendar and burn rate"
                   :flush="true">
            <x-slot:action>
                <a href="{{ route('awards.index') }}" class="small">View all</a>
            </x-slot:action>

            <div class="list">
                @forelse($activeAwards as $award)
                    @php $burn = $award->burnRate(); @endphp
                    <a href="{{ route('awards.show', $award) }}" class="list-row">
                        <span class="dot dot-{{ $award->traffic_light === 'red' ? 'red' : ($award->traffic_light === 'yellow' ? 'amber' : 'green') }}"
                              title="Compliance risk: {{ $award->traffic_light }}"></span>

                        <div class="list-main">
                            <span class="list-title">{{ $award->title }}</span>
                            <span class="list-sub mono">{{ $award->contract_number }} · {{ $award->proposal?->leadPi?->name }}</span>
                        </div>

                        <div style="width:170px; min-width:120px;">
                            <x-ui.meter :value="$burn" label="Burn rate" />
                        </div>

                        <div style="width:130px; text-align:right;">
                            <div style="font-size:12.5px; font-weight:600;">{{ $award->currency }} {{ number_format($award->total_budget, 0) }}</div>
                            <div class="tiny muted-3">Ends {{ $award->end_date?->format('d M Y') }}</div>
                        </div>
                    </a>
                @empty
                    <x-ui.empty icon="fa-trophy" title="No active awards">
                        Awards appear here once a proposal is funded and the contract is recorded.
                    </x-ui.empty>
                @endforelse
            </div>
        </x-ui.card>

        <div class="stack">
            <x-ui.card title="Funding calls closing soon" :flush="true">
                <x-slot:action><a href="{{ route('calls.index') }}" class="small">All calls</a></x-slot:action>

                <div class="list">
                    @forelse($recentCalls as $call)
                        @php $days = $call->days_until_deadline; @endphp
                        <a href="{{ route('calls.show', $call) }}" class="list-row">
                            <div class="list-main">
                                <span class="list-title">{{ $call->title }}</span>
                                <span class="list-sub">
                                    {{ $call->donor?->acronym ?? $call->donor?->name }}
                                    @if($call->estimated_budget) · {{ $call->currency }} {{ number_format($call->estimated_budget, 0) }} @endif
                                    · {{ $call->deadline?->format('d M Y') }}
                                </span>
                            </div>
                            <x-ui.pill :tone="$days <= 7 ? 'danger' : ($days <= 14 ? 'warn' : null)">
                                {{ $days }} {{ Str::plural('day', $days) }}
                            </x-ui.pill>
                        </a>
                    @empty
                        <x-ui.empty icon="fa-bullhorn">No calls are open at the moment.</x-ui.empty>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Deliverables due" note="Next 30 days across the portfolio" :flush="true">
                <div class="list">
                    @forelse($upcomingDeadlines as $item)
                        @php $days = Days::until($item->due_date); @endphp
                        <a href="{{ route('awards.show', $item->award_id) }}" class="list-row">
                            <div class="list-main">
                                <span class="list-title">{{ $item->title }}</span>
                                <span class="list-sub">{{ Str::limit($item->award?->title, 40) }}</span>
                            </div>
                            <x-ui.pill :tone="$days < 0 ? 'danger' : ($days <= 14 ? 'warn' : null)">
                                {{ $days < 0 ? abs($days) . 'd overdue' : $days . 'd' }}
                            </x-ui.pill>
                        </a>
                    @empty
                        <x-ui.empty icon="fa-calendar-check">Nothing due in the next 30 days.</x-ui.empty>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    </div>
</div>
@endsection
