@extends('layouts.gmu')

@section('title', 'Researcher profiles')
@section('crumb', 'Proposals')

@section('content')
<x-ui.page-head
    title="Researcher profiles"
    :lede="$researchers->total() . ' ' . Str::plural('researcher', $researchers->total()) . ' · expertise, track record and conflict-of-interest status'" />

<div class="grid grid-cards">
    @forelse($researchers as $researcher)
        @php
            $profile   = $researcher->researcherProfile;
            $expertise = collect($profile?->expertise_areas ?? []);
            $initials  = collect(explode(' ', $researcher->name))->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');
        @endphp

        <div class="card">
            <div class="card-body" style="display:flex; flex-direction:column; gap:16px;">
                <div class="row" style="gap:14px;">
                    <div class="avatar" style="width:46px; height:46px; flex:0 0 46px; font-size:15px;">{{ strtoupper($initials) }}</div>
                    <div style="min-width:0;">
                        <a href="{{ route('researchers.show', $researcher) }}"
                           style="font-size:14.5px; font-weight:600; color:var(--ink);">
                            {{ $profile?->title ? $profile->title . ' ' : '' }}{{ $researcher->name }}
                        </a>
                        <div class="tiny muted-3" style="margin-top:3px;">
                            {{ $profile?->position ?? $researcher->role?->display_name }}
                            @if($researcher->research_center) · {{ $researcher->research_center }}@endif
                        </div>
                    </div>
                </div>

                @if($expertise->isNotEmpty())
                    <div class="row" style="gap:6px;">
                        @foreach($expertise->take(3) as $area)
                            <x-ui.pill>{{ $area }}</x-ui.pill>
                        @endforeach
                        @if($expertise->count() > 3)<span class="tiny muted-3">+{{ $expertise->count() - 3 }}</span>@endif
                    </div>
                @else
                    <p class="small muted-3">No expertise areas recorded yet.</p>
                @endif

                <div class="row-between" style="padding-top:12px; border-top:1px solid var(--line-soft);">
                    <div class="kv">
                        <span class="k">Grants led</span>
                        <span class="v">{{ $profile?->total_grants_led ?? 0 }}</span>
                    </div>
                    <div class="kv">
                        <span class="k">Publications</span>
                        <span class="v">{{ count($profile?->publications ?? []) }}</span>
                    </div>
                    <div class="kv">
                        <span class="k">ORCID</span>
                        <span class="v">{{ $profile?->orcid_id ? 'Linked' : '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card" style="grid-column:1/-1;">
            <x-ui.empty icon="fa-user-graduate" title="No researcher profiles">
                Profiles are what the matching engine reads when a call comes in. They are created by the researchers
                themselves, or by the GMU on their behalf.
            </x-ui.empty>
        </div>
    @endforelse
</div>

<div class="pagination-wrap">{{ $researchers->links() }}</div>
@endsection
