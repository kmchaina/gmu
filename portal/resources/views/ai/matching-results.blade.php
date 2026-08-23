@extends('layouts.gmu')

@section('title', 'Match results')
@section('crumb', 'Intelligence')

@section('content')
<div class="page-head">
    <div style="min-width:0;">
        <a href="{{ route('ai.matching') }}" class="small">&larr; Grant matching</a>
        <h1 style="margin-top:8px;">Who fits this call</h1>
        <p class="lede">{{ $call->title }} · {{ $call->donor?->name }} · closes {{ $call->deadline?->format('d M Y') }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('calls.show', $call) }}" class="btn">Open the call</a>
    </div>
</div>

<div class="stack">
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <div>
            Scores are computed from your own records, not guessed: expertise against the call theme (25),
            funded track record (20), budget fit against past awards (20), relevant qualifications (15),
            publications (10), and no unresolved conflict of interest (5). The breakdown is shown for each person.
        </div>
    </div>

    <div class="stack-sm">
        @forelse($matches as $index => $match)
            @php
                $score = $match['score'];
                $tone  = $score >= 75 ? 'ok' : ($score >= 55 ? 'info' : ($score >= 35 ? 'warn' : null));
            @endphp

            <x-ui.card>
                <div class="row" style="gap:20px; align-items:flex-start;">
                    <div style="width:64px; text-align:center; flex:0 0 64px;">
                        <div style="font-size:26px; font-weight:600; letter-spacing:-1px; line-height:1;
                                    color: var(--{{ $tone === 'ok' ? 'ok-ink' : ($tone === 'warn' ? 'warn-ink' : 'navy-500') }});">
                            {{ $score }}
                        </div>
                        <div class="tiny muted-3" style="margin-top:4px;">/ 100</div>
                    </div>

                    <div style="flex:1; min-width:0;">
                        <div class="row-between">
                            <div>
                                <span style="font-size:14.5px; font-weight:600;">{{ $match['researcher'] }}</span>
                                <x-ui.pill :tone="$tone" style="margin-left:8px;">{{ $match['grade'] }}</x-ui.pill>
                            </div>
                            @if($index === 0)<x-ui.pill tone="navy">Best fit</x-ui.pill>@endif
                        </div>

                        <div class="meter {{ $score < 35 ? 'danger' : ($score < 55 ? 'warn' : '') }}" style="margin:12px 0 14px;">
                            <span style="width: {{ $score }}%"></span>
                        </div>

                        @forelse($match['reasons'] as $reason)
                            <div class="row-between small" style="padding:5px 0; border-top:1px solid var(--line-soft);">
                                <span>{{ $reason['factor'] }} <span class="muted-3">— {{ $reason['detail'] }}</span></span>
                                <span class="mono" style="font-weight:600;">+{{ $reason['points'] }}</span>
                            </div>
                        @empty
                            <p class="small muted-3">Nothing on record scored for this researcher — their profile may be empty.</p>
                        @endforelse

                        @if(! empty($match['ai_summary']))
                            <div style="margin-top:14px; padding:12px 14px; background:var(--canvas); border-radius:8px;">
                                <div class="eyebrow" style="margin-bottom:6px;">Summary</div>
                                <p class="small muted" style="line-height:1.7;">{{ $match['ai_summary'] }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </x-ui.card>
        @empty
            <x-ui.card>
                <x-ui.empty icon="fa-user-graduate" title="Nobody to score">
                    Matching needs active researchers with profiles. Ask them to fill in their expertise areas and
                    qualifications, or add these on their behalf.
                </x-ui.empty>
            </x-ui.card>
        @endforelse
    </div>
</div>
@endsection
