@extends('layouts.gmu')

@section('title', 'Funding calls')
@section('crumb', 'Grant opportunities')

@section('content')
@php
    $openCount   = $calls->where('status', 'open')->count();
    $closedCount = $calls->where('status', '!=', 'open')->count();
    $filter      = request('filter') ?: request('status');
@endphp

<x-ui.page-head
    title="Funding calls"
    :lede="$calls->total() . ' ' . Str::plural('call', $calls->total()) . ' on record · ' . $openCount . ' open on this page'">
    <x-slot:actions>
        @can('create', App\Models\FundingCall::class)
            <a href="{{ route('calls.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Post a call</a>
        @endcan
    </x-slot:actions>
</x-ui.page-head>

<div class="stack">

    <div class="row-between">
        <div class="chips">
            <a href="{{ route('calls.index') }}" class="chip {{ ! $filter ? 'is-active' : '' }}">All</a>
            <a href="{{ route('calls.index', ['status' => 'open']) }}" class="chip {{ $filter === 'open' ? 'is-active' : '' }}">Open</a>
            <a href="{{ route('calls.index', ['filter' => 'closing_soon']) }}" class="chip {{ $filter === 'closing_soon' ? 'is-active' : '' }}">Closing soon</a>
            <a href="{{ route('calls.index', ['status' => 'closed']) }}" class="chip {{ $filter === 'closed' ? 'is-active' : '' }}">Closed</a>
            <a href="{{ route('calls.index', ['status' => 'draft']) }}" class="chip {{ $filter === 'draft' ? 'is-active' : '' }}">Draft</a>
        </div>

        <form method="GET" class="row" style="gap:8px;">
            @if($filter)<input type="hidden" name="{{ request('filter') ? 'filter' : 'status' }}" value="{{ $filter }}">@endif
            <select name="donor_id" class="select" style="width:190px;" onchange="this.form.submit()">
                <option value="">All donors</option>
                @foreach($donors as $donor)
                    <option value="{{ $donor->id }}" @selected(request('donor_id') == $donor->id)>{{ $donor->acronym ?? $donor->name }}</option>
                @endforeach
            </select>
            <input type="search" name="search" class="input" style="width:200px;" placeholder="Search title or theme"
                   value="{{ request('search') }}">
            <button class="btn"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <div class="grid grid-cards">
        @forelse($calls as $call)
            @php
                $days   = $call->days_until_deadline;
                $urgent = $call->status === 'open' && $days <= 7;
                $soon   = $call->status === 'open' && $days > 7 && $days <= 14;
            @endphp

            <div class="card {{ $urgent ? 'accent-danger' : '' }}">
                <div class="card-body" style="display:flex; flex-direction:column; gap:16px;">
                    <div class="row-between" style="align-items:flex-start;">
                        <div>
                            <div class="eyebrow">
                                {{ $call->donor?->acronym ?? $call->donor?->name }}@if($call->theme) · {{ $call->theme }}@endif
                            </div>
                            <a href="{{ route('calls.show', $call) }}"
                               style="display:block; margin-top:7px; font-size:15px; font-weight:600; letter-spacing:-.2px; line-height:1.35; color:var(--ink);">
                                {{ $call->title }}
                            </a>
                        </div>

                        @if($call->status === 'open')
                            <x-ui.pill :tone="$urgent ? 'danger' : ($soon ? 'warn' : null)">
                                {{ $days < 0 ? 'Closed' : 'Closes in ' . $days . 'd' }}
                            </x-ui.pill>
                        @else
                            <x-ui.pill>{{ ucfirst($call->status) }}</x-ui.pill>
                        @endif
                    </div>

                    @if($call->description)
                        <p class="small" style="color:var(--muted); line-height:1.6;">{{ Str::limit($call->description, 150) }}</p>
                    @endif

                    <div class="row-between" style="padding-top:14px; border-top:1px solid var(--line-soft);">
                        <div class="kv">
                            <span class="k">Estimated budget</span>
                            <span class="v">{{ $call->estimated_budget ? $call->currency . ' ' . number_format($call->estimated_budget, 0) : 'Not stated' }}</span>
                        </div>
                        <div class="kv">
                            <span class="k">Deadline</span>
                            <span class="v">{{ $call->deadline?->format('d M Y') }}</span>
                        </div>
                        <div class="kv">
                            <span class="k">Interest</span>
                            <span class="v">
                                @if($call->interests_count > 0)
                                    {{ $call->interests_count }} logged
                                @else
                                    None logged
                                @endif
                            </span>
                        </div>
                        <div class="kv">
                            <span class="k">Proposals</span>
                            <span class="v">{{ $call->proposals_count }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card" style="grid-column:1/-1;">
                <x-ui.empty icon="fa-bullhorn" title="No calls match">
                    Nothing here yet. Calls posted by the GMU appear as cards, newest deadline first.
                    <x-slot:action>
                        @can('create', App\Models\FundingCall::class)
                            <a href="{{ route('calls.create') }}" class="btn btn-primary">Post the first call</a>
                        @endcan
                    </x-slot:action>
                </x-ui.empty>
            </div>
        @endforelse
    </div>

    <div class="pagination-wrap">{{ $calls->links() }}</div>
</div>
@endsection
