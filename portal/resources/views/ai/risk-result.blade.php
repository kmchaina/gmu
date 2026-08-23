@extends('layouts.gmu')

@section('title', 'Risk analysis')
@section('crumb', 'Intelligence')

@section('content')
@php
    $overall = $result['overall_risk'];
    $tone    = match($overall) { 'high' => 'danger', 'medium' => 'warn', 'low' => 'info', default => 'ok' };
    $flags   = collect($result['flags']);
@endphp

<div class="page-head">
    <div style="min-width:0;">
        <a href="{{ route('ai.risk') }}" class="small">&larr; Compliance risk</a>
        <h1 style="margin-top:8px;">{{ $award->title }}</h1>
        <div class="row" style="gap:12px; margin-top:10px;">
            <span class="pill mono">{{ $award->contract_number }}</span>
            <x-ui.pill :tone="$tone">
                {{ $overall === 'none' ? 'No flags' : ucfirst($overall) . ' risk' }}
            </x-ui.pill>
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('awards.show', $award) }}" class="btn btn-primary">Open the award</a>
    </div>
</div>

<div class="stack">

    <div class="stats">
        <x-ui.stat label="Overall risk" :value="$overall === 'none' ? 'Clear' : ucfirst($overall)" :tone="$tone === 'ok' ? 'ok' : ($tone === 'danger' ? 'danger' : 'warn')"
                   :note="$flags->count() . ' ' . Str::plural('flag', $flags->count()) . ' raised'" />
        <x-ui.stat label="High severity" :value="$flags->where('level', 'high')->count()"
                   :tone="$flags->where('level', 'high')->count() > 0 ? 'danger' : 'ok'" note="Act on these first" />
        <x-ui.stat label="Medium" :value="$flags->where('level', 'medium')->count()" note="Plan for these" />
        <x-ui.stat label="Low" :value="$flags->where('level', 'low')->count()" note="Awareness only" />
    </div>

    @if($result['ai_narrative'])
        <x-ui.card title="Summary" note="Written from the flags below">
            <p style="line-height:1.8; color:var(--muted);">{{ $result['ai_narrative'] }}</p>
        </x-ui.card>
    @endif

    <x-ui.card title="Flags" :note="'Computed from the award records · ' . $result['provider']" :flush="true">
        <div class="list">
            @forelse($flags as $flag)
                <div class="list-row" style="align-items:flex-start;">
                    <span class="dot dot-{{ $flag['level'] === 'high' ? 'red' : ($flag['level'] === 'medium' ? 'amber' : 'green') }}"
                          style="margin-top:5px;"></span>
                    <div class="list-main">
                        <span class="list-title">{{ $flag['message'] }}</span>
                        <span class="list-sub">{{ $flag['category'] }}</span>
                    </div>
                    <x-ui.pill :tone="$flag['level'] === 'high' ? 'danger' : ($flag['level'] === 'medium' ? 'warn' : null)">
                        {{ strtoupper($flag['level']) }}
                    </x-ui.pill>
                </div>
            @empty
                <x-ui.empty icon="fa-check-circle" title="Nothing flagged">
                    Reports are current, ethics approval is valid, deliverables are on schedule and the burn rate is
                    within range.
                </x-ui.empty>
            @endforelse
        </div>
    </x-ui.card>

    @unless($result['llm_active'])
        <div class="alert alert-info" style="margin:0;">
            <i class="fas fa-info-circle"></i>
            <span>
                These flags come from rules in the codebase, not a language model. Configuring one adds a written
                summary above; the analysis itself is unchanged.
            </span>
        </div>
    @endunless
</div>
@endsection
