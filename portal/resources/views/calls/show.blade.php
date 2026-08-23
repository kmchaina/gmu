@extends('layouts.gmu')

@section('title', Str::limit($call->title, 40))
@section('crumb', 'Funding call')

@section('content')
@php
    $days = $call->days_until_deadline;
    $tone = $call->status !== 'open' ? null : ($days <= 7 ? 'danger' : ($days <= 14 ? 'warn' : null));
@endphp

<div class="page-head">
    <div style="min-width:0;">
        <a href="{{ route('calls.index') }}" class="small">&larr; Funding calls</a>
        <h1 style="margin-top:8px;">{{ $call->title }}</h1>
        <div class="row" style="gap:12px; margin-top:10px;">
            <x-ui.pill :tone="$tone">
                {{ $call->status === 'open' ? ($days < 0 ? 'Deadline passed' : 'Closes in ' . $days . ' days') : ucfirst($call->status) }}
            </x-ui.pill>
            <span class="small muted">{{ $call->donor?->name }}@if($call->theme) · {{ $call->theme }}@endif</span>
            @if($call->solicitation_number)<span class="mono small muted-3">{{ $call->solicitation_number }}</span>@endif
        </div>
    </div>
    <div class="page-actions">
        @if($call->call_document)
            <a href="{{ route('calls.document', $call) }}" class="btn"><i class="fas fa-file-pdf"></i> Call document</a>
        @endif
        @can('update', $call)
            <a href="{{ route('calls.edit', $call) }}" class="btn"><i class="fas fa-pen"></i> Edit</a>
        @endcan
        @can('create', App\Models\Proposal::class)
            <a href="{{ route('proposals.create', ['funding_call_id' => $call->id]) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Register a proposal
            </a>
        @endcan
    </div>
</div>

<div class="stack">

    <div class="stats">
        <x-ui.stat label="Deadline" :value="$call->deadline?->format('d M Y')"
                   :note="$days < 0 ? abs($days) . ' days ago' : $days . ' days remaining'" :tone="$tone" />
        <x-ui.stat label="Estimated budget"
                   :value="$call->estimated_budget ? $call->currency . ' ' . number_format($call->estimated_budget, 0) : 'Not stated'"
                   note="As advertised by the donor" />
        <x-ui.stat label="Expressions of interest" :value="$call->interests->count()"
                   :note="$call->interests->where('status', 'selected')->count() . ' selected'" />
        <x-ui.stat label="Proposals" :value="$call->proposals->count()" note="Registered against this call" />
    </div>

    <div class="grid grid-wide">
        <div class="stack">
            <x-ui.card title="About this call">
                @if($call->description)
                    <p style="line-height:1.75; color:var(--muted);">{{ $call->description }}</p>
                @else
                    <p class="small muted-3">No description was recorded.</p>
                @endif

                @if($call->eligibility)
                    <div class="divider"></div>
                    <div class="eyebrow" style="margin-bottom:8px;">Eligibility</div>
                    <p class="small" style="line-height:1.75; color:var(--muted);">{{ $call->eligibility }}</p>
                @endif
            </x-ui.card>

            {{-- Expressions of interest --}}
            @if($canSeeInterests || $userInterest)
                <x-ui.card title="Expressions of interest"
                           :note="$canSeeInterests ? 'Who has put themselves forward, and the GMU decision' : 'Your submission'"
                           :flush="true">
                    <div class="list">
                        @forelse($call->interests as $interest)
                            <div class="list-row" style="align-items:flex-start;">
                                <div class="list-main">
                                    <span class="list-title">{{ $interest->user?->name }}</span>
                                    <span class="list-sub">{{ $interest->user?->department }}</span>
                                    @if($interest->motivation)
                                        <p class="small muted" style="margin-top:8px; line-height:1.65;">{{ $interest->motivation }}</p>
                                    @endif
                                    @if($interest->gmu_notes)
                                        <p class="small" style="margin-top:6px; color:var(--muted-2);"><em>GMU note:</em> {{ $interest->gmu_notes }}</p>
                                    @endif
                                </div>
                                <x-ui.pill :tone="$interest->status === 'selected' ? 'ok' : ($interest->status === 'not_selected' ? 'danger' : 'warn')">
                                    {{ Str::headline($interest->status) }}
                                </x-ui.pill>
                            </div>
                        @empty
                            <x-ui.empty icon="fa-hand-paper">Nobody has expressed interest yet.</x-ui.empty>
                        @endforelse
                    </div>
                </x-ui.card>
            @endif

            {{-- Team selection --}}
            @can('selectTeam', $call)
                @if($call->interests->isNotEmpty())
                    <x-ui.card title="Select the team" note="NIMR submits one proposal per call — choose who takes it forward">
                        <form method="POST" action="{{ route('calls.select-team', $call) }}">
                            @csrf
                            <div class="stack-sm">
                                @foreach($call->interests as $interest)
                                    <label class="check">
                                        <input type="checkbox" name="selected_user_ids[]" value="{{ $interest->user_id }}"
                                               @checked($interest->status === 'selected')>
                                        <span>{{ $interest->user?->name }} <span class="muted-3">— {{ $interest->user?->department }}</span></span>
                                    </label>
                                @endforeach
                            </div>
                            <x-ui.field label="Note to those selected" name="gmu_notes" class="wide">
                                <textarea name="gmu_notes" rows="2" class="textarea" style="margin-top:14px;"></textarea>
                            </x-ui.field>
                            <div class="form-actions"><button class="btn btn-primary">Save selection and notify</button></div>
                        </form>
                    </x-ui.card>
                @endif
            @endcan

            {{-- Proposals against this call --}}
            <x-ui.card title="Proposals" :flush="true">
                <div class="list">
                    @forelse($call->proposals as $proposal)
                        <a href="{{ route('proposals.show', $proposal) }}" class="list-row">
                            <div class="list-main">
                                <span class="list-title">{{ $proposal->title }}</span>
                                <span class="list-sub">Registered {{ $proposal->created_at?->format('d M Y') }}</span>
                            </div>
                            <x-ui.pill>{{ Str::headline($proposal->status) }}</x-ui.pill>
                        </a>
                    @empty
                        <x-ui.empty icon="fa-file-alt">No proposal registered against this call yet.</x-ui.empty>
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        <div class="stack">
            @can('expressInterest', $call)
                <x-ui.card title="Express interest" note="Tell the GMU you intend to apply">
                    @if($userInterest)
                        <x-ui.pill :tone="$userInterest->status === 'selected' ? 'ok' : ($userInterest->status === 'not_selected' ? 'danger' : 'warn')">
                            {{ Str::headline($userInterest->status) }}
                        </x-ui.pill>
                        <p class="small muted" style="margin-top:12px; line-height:1.7;">
                            You expressed interest on {{ $userInterest->created_at?->format('d M Y') }}.
                            @if($userInterest->status === 'pending') The GMU will confirm the team shortly. @endif
                        </p>
                    @endif

                    <form method="POST" action="{{ route('calls.interest', $call) }}" style="margin-top:{{ $userInterest ? '16px' : '0' }};">
                        @csrf
                        <x-ui.field label="Why this call fits your work" name="motivation" help="At least 50 characters">
                            <textarea name="motivation" rows="3" class="textarea" required>{{ old('motivation', $userInterest?->motivation) }}</textarea>
                        </x-ui.field>
                        <x-ui.field label="Relevant experience" name="relevant_experience" help="At least 30 characters">
                            <textarea name="relevant_experience" rows="3" class="textarea" required>{{ old('relevant_experience', $userInterest?->relevant_experience) }}</textarea>
                        </x-ui.field>
                        <div class="form-actions">
                            <button class="btn btn-primary btn-block">{{ $userInterest ? 'Update my submission' : 'Express interest' }}</button>
                        </div>
                    </form>
                </x-ui.card>
            @endcan

            <x-ui.card title="Call details">
                <div class="stack-sm small">
                    <div class="row-between"><span class="muted">Donor</span><span>{{ $call->donor?->name }}</span></div>
                    <div class="row-between"><span class="muted">Theme</span><span>{{ $call->theme ?? '—' }}</span></div>
                    <div class="row-between"><span class="muted">Opened</span><span>{{ $call->open_date?->format('d M Y') ?? '—' }}</span></div>
                    <div class="row-between"><span class="muted">Deadline</span><span>{{ $call->deadline?->format('d M Y') }}</span></div>
                    <div class="row-between"><span class="muted">Origin</span><span>{{ Str::headline($call->initiation_type) }}</span></div>
                    <div class="row-between"><span class="muted">Posted by</span><span>{{ $call->postedBy?->name }}</span></div>
                </div>
            </x-ui.card>

            @can('view-analytics')
                <x-ui.card title="Who fits this call?" note="Rule-based matching across active researchers">
                    <a href="{{ route('ai.match-call', $call) }}" class="btn btn-block">
                        <i class="fas fa-robot"></i> Score researchers
                    </a>
                </x-ui.card>
            @endcan
        </div>
    </div>
</div>
@endsection
