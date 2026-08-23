@extends('layouts.gmu')

@section('title', 'Grant matching')
@section('crumb', 'Intelligence')

@section('content')
<x-ui.page-head
    title="Grant matching"
    lede="Choose an open call to score every active researcher against it — expertise, funded track record, qualifications, publications, budget fit and conflict-of-interest status.">
    <x-slot:actions>
        <a href="{{ route('ai.hub') }}" class="btn">&larr; AI hub</a>
    </x-slot:actions>
</x-ui.page-head>

<div class="grid grid-cards">
    @forelse($calls as $call)
        @php $days = $call->days_until_deadline; @endphp
        <div class="card">
            <div class="card-body" style="display:flex; flex-direction:column; gap:14px;">
                <div class="row-between" style="align-items:flex-start;">
                    <div>
                        <div class="eyebrow">{{ $call->donor?->acronym ?? $call->donor?->name }}@if($call->theme) · {{ $call->theme }}@endif</div>
                        <div style="margin-top:7px; font-size:14.5px; font-weight:600; line-height:1.35;">{{ $call->title }}</div>
                    </div>
                    <x-ui.pill :tone="$days <= 7 ? 'danger' : ($days <= 14 ? 'warn' : null)">{{ $days }}d</x-ui.pill>
                </div>

                <div class="row-between" style="padding-top:12px; border-top:1px solid var(--line-soft);">
                    <span class="tiny muted-3">Closes {{ $call->deadline?->format('d M Y') }}</span>
                    <a href="{{ route('ai.match-call', $call) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-user-check"></i> Score researchers
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="card" style="grid-column:1/-1;">
            <x-ui.empty icon="fa-bullhorn" title="No open calls">
                Matching runs against open calls. Post one, and every active researcher will be scored against it.
            </x-ui.empty>
        </div>
    @endforelse
</div>
@endsection
