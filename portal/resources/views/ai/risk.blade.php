@extends('layouts.gmu')

@section('title', 'Compliance risk')
@section('crumb', 'Intelligence')

@section('content')
<x-ui.page-head
    title="Compliance risk analysis"
    lede="Choose an award to read it end to end — overdue and upcoming reports, deliverables, ethics expiry, burn rate against the period remaining, and the closeout window.">
    <x-slot:actions>
        <a href="{{ route('ai.hub') }}" class="btn">&larr; AI hub</a>
    </x-slot:actions>
</x-ui.page-head>

<div class="grid grid-cards">
    @forelse($awards as $award)
        <div class="card">
            <div class="card-body" style="display:flex; flex-direction:column; gap:14px;">
                <div>
                    <div class="eyebrow">{{ $award->donor?->acronym ?? $award->donor?->name }}</div>
                    <div style="margin-top:7px; font-size:14.5px; font-weight:600; line-height:1.35;">{{ $award->title }}</div>
                    <div class="tiny mono muted-3" style="margin-top:6px;">{{ $award->contract_number }}</div>
                </div>

                <div class="row-between" style="padding-top:12px; border-top:1px solid var(--line-soft);">
                    <span class="tiny muted-3">Ends {{ $award->end_date?->format('d M Y') }}</span>
                    <a href="{{ route('ai.risk-analysis', $award) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-radiation"></i> Analyse
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="card" style="grid-column:1/-1;">
            <x-ui.empty icon="fa-trophy" title="No active awards">
                Risk analysis runs against active awards.
            </x-ui.empty>
        </div>
    @endforelse
</div>
@endsection
