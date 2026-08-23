@extends('layouts.gmu')

@section('title', $donor->name)
@section('crumb', 'Donor')

@section('content')
<div class="page-head">
    <div style="min-width:0;">
        <a href="{{ route('donors.index') }}" class="small">&larr; Donors</a>
        <h1 style="margin-top:8px;">{{ $donor->name }}</h1>
        <div class="row" style="gap:12px; margin-top:10px;">
            <x-ui.pill>{{ Str::headline($donor->type) }}</x-ui.pill>
            @if($donor->acronym)<span class="mono small muted-3">{{ $donor->acronym }}</span>@endif
            @if($donor->country)<span class="small muted">{{ $donor->country }}</span>@endif
        </div>
    </div>
    <div class="page-actions">
        @can('update', $donor)
            <a href="{{ route('donors.edit', $donor) }}" class="btn"><i class="fas fa-pen"></i> Edit</a>
        @endcan
        @can('create', App\Models\FundingCall::class)
            <a href="{{ route('calls.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Post a call</a>
        @endcan
    </div>
</div>

<div class="stack">
    <div class="grid grid-wide">
        <div class="stack">
            <x-ui.card title="Funding calls" :flush="true">
                <div class="list">
                    @forelse($donor->fundingCalls as $call)
                        <a href="{{ route('calls.show', $call) }}" class="list-row">
                            <div class="list-main">
                                <span class="list-title">{{ $call->title }}</span>
                                <span class="list-sub">Deadline {{ $call->deadline?->format('d M Y') }}@if($call->theme) · {{ $call->theme }}@endif</span>
                            </div>
                            <x-ui.pill :tone="$call->status === 'open' ? 'ok' : null">{{ ucfirst($call->status) }}</x-ui.pill>
                        </a>
                    @empty
                        <x-ui.empty icon="fa-bullhorn">No calls recorded for this donor.</x-ui.empty>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Awards" :flush="true">
                <div class="list">
                    @forelse($donor->awards as $award)
                        <a href="{{ route('awards.show', $award) }}" class="list-row">
                            <div class="list-main">
                                <span class="list-title">{{ $award->title }}</span>
                                <span class="list-sub mono">{{ $award->contract_number }} · {{ $award->proposal?->leadPi?->name }}</span>
                            </div>
                            <span class="small mono">{{ $award->currency }} {{ number_format($award->total_budget, 0) }}</span>
                        </a>
                    @empty
                        <x-ui.empty icon="fa-trophy">No awards from this donor yet.</x-ui.empty>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Guidelines and templates" :flush="true">
                <div class="list">
                    @forelse($donor->policies as $policy)
                        <a href="{{ route('donor-policies.show', $policy) }}" class="list-row">
                            <div class="list-main">
                                <span class="list-title">{{ $policy->title }}</span>
                                <span class="list-sub">{{ Str::headline($policy->policy_type) }} · v{{ $policy->version }}</span>
                            </div>
                            <i class="fas fa-download muted-3"></i>
                        </a>
                    @empty
                        <x-ui.empty icon="fa-book">No guidelines filed for this donor.</x-ui.empty>
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        <div class="stack">
            <x-ui.card title="Donor details">
                <div class="stack-sm small">
                    <div class="row-between"><span class="muted">Type</span><span>{{ Str::headline($donor->type) }}</span></div>
                    <div class="row-between"><span class="muted">Country</span><span>{{ $donor->country ?? '—' }}</span></div>
                    <div class="row-between"><span class="muted">Contact</span><span>{{ $donor->contact_email ?? '—' }}</span></div>
                    @if($donor->website)
                        <div class="row-between"><span class="muted">Website</span>
                            <a href="{{ $donor->website }}" target="_blank" rel="noopener">Open</a></div>
                    @endif
                </div>
                @if($donor->notes)
                    <div class="divider"></div>
                    <p class="small muted" style="line-height:1.7;">{{ $donor->notes }}</p>
                @endif
            </x-ui.card>
        </div>
    </div>
</div>
@endsection
