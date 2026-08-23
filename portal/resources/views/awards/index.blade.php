@extends('layouts.gmu')

@section('title', 'Active awards')
@section('crumb', 'Awards & compliance')

@section('content')
@php
    use App\Support\Days;
    $current = request('status');
@endphp

<x-ui.page-head
    title="Active awards"
    :lede="$awards->total() . ' ' . Str::plural('award', $awards->total()) . ' · risk from the compliance engine: reports, ethics, calendar and burn rate'">
    <x-slot:actions>
        @can('create', App\Models\Award::class)
            <a href="{{ route('awards.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Record an award</a>
        @endcan
    </x-slot:actions>
</x-ui.page-head>

<div class="stack">

    <div class="row-between">
        <div class="chips">
            <a href="{{ route('awards.index') }}" class="chip {{ ! $current ? 'is-active' : '' }}">All</a>
            @foreach(['active' => 'Active', 'completed' => 'Completed', 'suspended' => 'Suspended', 'terminated' => 'Terminated'] as $value => $label)
                <a href="{{ route('awards.index', ['status' => $value]) }}" class="chip {{ $current === $value ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>

        <form method="GET" class="row" style="gap:8px;">
            @if($current)<input type="hidden" name="status" value="{{ $current }}">@endif
            <select name="donor_id" class="select" style="width:180px;" onchange="this.form.submit()">
                <option value="">All donors</option>
                @foreach($donors as $donor)
                    <option value="{{ $donor->id }}" @selected(request('donor_id') == $donor->id)>{{ $donor->acronym ?? $donor->name }}</option>
                @endforeach
            </select>
            <input type="search" name="search" class="input" style="width:200px;" placeholder="Title or contract number"
                   value="{{ request('search') }}">
            <button class="btn"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <div class="grid grid-cards">
        @forelse($awards as $award)
            @php
                $burn      = $award->burnRate();
                $light     = $award->traffic_light;
                $daysLeft  = Days::until($award->end_date);
                $accent    = $light === 'red' ? 'danger' : ($light === 'yellow' ? 'warn' : null);
            @endphp

            <div class="card {{ $accent ? "accent-{$accent}" : '' }}">
                <div class="card-body" style="display:flex; flex-direction:column; gap:16px;">
                    <div class="row-between" style="align-items:flex-start;">
                        <div style="min-width:0;">
                            <div class="eyebrow">{{ $award->donor?->acronym ?? $award->donor?->name }}</div>
                            <a href="{{ route('awards.show', $award) }}"
                               style="display:block; margin-top:7px; font-size:15px; font-weight:600; letter-spacing:-.2px; line-height:1.35; color:var(--ink);">
                                {{ $award->title }}
                            </a>
                            <div class="tiny mono muted-3" style="margin-top:6px;">
                                {{ $award->contract_number }} · {{ $award->proposal?->leadPi?->name }}
                            </div>
                        </div>
                        <span class="dot dot-{{ $light === 'red' ? 'red' : ($light === 'yellow' ? 'amber' : 'green') }}"
                              title="Compliance risk: {{ $light }}"></span>
                    </div>

                    <x-ui.meter :value="$burn" label="Budget used" />

                    <div class="row-between" style="padding-top:14px; border-top:1px solid var(--line-soft);">
                        <div class="kv">
                            <span class="k">Value</span>
                            <span class="v">{{ $award->currency }} {{ number_format($award->total_budget, 0) }}</span>
                        </div>
                        <div class="kv">
                            <span class="k">Period ends</span>
                            <span class="v">{{ $award->end_date?->format('d M Y') }}</span>
                        </div>
                        <div class="kv">
                            <span class="k">{{ $daysLeft < 0 ? 'Ended' : 'Remaining' }}</span>
                            <span class="v">{{ $daysLeft < 0 ? abs($daysLeft) . 'd ago' : $daysLeft . ' days' }}</span>
                        </div>
                        <div class="kv">
                            <span class="k">Reports</span>
                            <span class="v">{{ $award->reports_count }}</span>
                        </div>
                    </div>

                    @if($award->overdue_reports_count > 0 || $award->due_soon_count > 0)
                        <div class="row" style="gap:8px;">
                            @if($award->overdue_reports_count > 0)
                                <x-ui.pill tone="danger">{{ $award->overdue_reports_count }} overdue {{ Str::plural('report', $award->overdue_reports_count) }}</x-ui.pill>
                            @endif
                            @if($award->due_soon_count > 0)
                                <x-ui.pill tone="warn">{{ $award->due_soon_count }} due within 14d</x-ui.pill>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="card" style="grid-column:1/-1;">
                <x-ui.empty icon="fa-trophy" title="No awards match">
                    Awards appear here once a funded proposal has its contract recorded. Each card shows the compliance
                    risk, the budget used, and what is overdue.
                </x-ui.empty>
            </div>
        @endforelse
    </div>

    <div class="pagination-wrap">{{ $awards->links() }}</div>
</div>
@endsection
