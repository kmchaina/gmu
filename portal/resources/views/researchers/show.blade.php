@extends('layouts.gmu')

@section('title', $user->name)
@section('crumb', 'Researcher profile')

@section('content')
@php
    $expertise = collect($profile?->expertise_areas ?? []);
    $quals     = collect($profile?->qualifications ?? []);
    $pubs      = collect($profile?->publications ?? []);
    $initials  = collect(explode(' ', $user->name))->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');
    $funded    = $proposals->filter(fn($p) => $p->award !== null);
@endphp

<div class="page-head">
    <div class="row" style="gap:18px; min-width:0;">
        <div class="avatar" style="width:58px; height:58px; flex:0 0 58px; font-size:19px;">{{ strtoupper($initials) }}</div>
        <div style="min-width:0;">
            <h1>{{ $profile?->title ? $profile->title . ' ' : '' }}{{ $user->name }}</h1>
            <p class="lede">
                {{ $profile?->position ?? $user->role?->display_name }}
                @if($user->research_center) · {{ $user->research_center }}@endif
                @if($profile?->orcid_id) · <span class="mono">ORCID {{ $profile->orcid_id }}</span>@endif
            </p>
        </div>
    </div>
    <div class="page-actions">
        @if($canEdit)
            <a href="{{ route('researchers.edit', $user) }}" class="btn btn-primary"><i class="fas fa-pen"></i> Edit profile</a>
        @endif
    </div>
</div>

<div class="stack">

    <div class="stats">
        <x-ui.stat label="Grants led" :value="$funded->count()" note="Proposals that became awards" />
        <x-ui.stat label="Proposals" :value="$proposals->count()" note="Across all stages" />
        <x-ui.stat label="Publications" :value="$pubs->count()" note="On record in this profile" />
        <x-ui.stat label="Expertise areas" :value="$expertise->count()"
                   note="Used by the matching engine" />
    </div>

    <div class="grid grid-wide">
        <div class="stack">
            <x-ui.card title="About">
                @if($profile?->bio)
                    <p style="line-height:1.75; color:var(--muted);">{{ $profile->bio }}</p>
                @else
                    <p class="small muted-3">No biography recorded yet.</p>
                @endif

                @if($expertise->isNotEmpty())
                    <div class="divider"></div>
                    <div class="eyebrow" style="margin-bottom:10px;">Expertise</div>
                    <div class="row" style="gap:7px;">
                        @foreach($expertise as $area)<x-ui.pill>{{ $area }}</x-ui.pill>@endforeach
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card title="Grant history" note="Proposals led, and what became of them" :flush="true">
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Proposal</th><th>Call</th><th>Status</th><th>Outcome</th></tr></thead>
                        <tbody>
                            @forelse($proposals as $proposal)
                                <tr>
                                    <td><a href="{{ route('proposals.show', $proposal) }}" class="strong">{{ Str::limit($proposal->title, 44) }}</a>
                                        <div class="tiny muted-3">{{ $proposal->created_at?->format('M Y') }}</div></td>
                                    <td class="small muted">{{ Str::limit($proposal->fundingCall?->title, 28) }}</td>
                                    <td><x-ui.pill :tone="match($proposal->status) {
                                        'awarded' => 'ok', 'declined' => 'danger', 'internal_review' => 'warn', default => null }">
                                        {{ Str::headline($proposal->status) }}</x-ui.pill></td>
                                    <td class="small">
                                        @if($proposal->award)
                                            <a href="{{ route('awards.show', $proposal->award) }}">{{ $proposal->award->contract_number }}</a>
                                        @else — @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-ui.empty icon="fa-file-alt">No proposals on record yet.</x-ui.empty></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            @if($pubs->isNotEmpty())
                <x-ui.card title="Publications" :flush="true">
                    <div class="list">
                        @foreach($pubs as $pub)
                            <div class="list-row">
                                <div class="list-main">
                                    <span class="list-title">{{ $pub['title'] ?? 'Untitled' }}</span>
                                    <span class="list-sub">
                                        {{ $pub['journal'] ?? '' }}@if(! empty($pub['year'])) · {{ $pub['year'] }}@endif
                                        @if(! empty($pub['doi'])) · <span class="mono">{{ $pub['doi'] }}</span>@endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            @endif
        </div>

        <div class="stack">
            <x-ui.card title="Qualifications">
                @forelse($quals as $qual)
                    <div style="padding:10px 0; border-bottom:1px solid var(--line-soft);">
                        <div style="font-size:13px; font-weight:600;">{{ $qual['degree'] ?? '' }} @if(! empty($qual['field'])) — {{ $qual['field'] }} @endif</div>
                        <div class="tiny muted-3">{{ $qual['institution'] ?? '' }}@if(! empty($qual['year'])) · {{ $qual['year'] }}@endif</div>
                    </div>
                @empty
                    <p class="small muted-3">No qualifications recorded.</p>
                @endforelse
            </x-ui.card>

            <x-ui.card title="Contact">
                <div class="stack-sm small">
                    <div class="row-between"><span class="muted">Email</span><span>{{ $user->email }}</span></div>
                    <div class="row-between"><span class="muted">Phone</span><span>{{ $profile?->phone ?? $user->phone ?? '—' }}</span></div>
                    <div class="row-between"><span class="muted">Department</span><span>{{ $user->department ?? '—' }}</span></div>
                    <div class="row-between"><span class="muted">Centre</span><span>{{ $user->research_center ?? '—' }}</span></div>
                </div>
            </x-ui.card>

            {{-- FCOI: confidential to the researcher and the Coordinator --}}
            @if($canSeeFcoi)
                <x-ui.card title="Conflict of interest" note="Visible to you and the Coordinator only" accent="warn" :flush="true">
                    <div class="list">
                        @forelse($fcois as $fcoi)
                            <div class="list-row" style="align-items:flex-start;">
                                <div class="list-main">
                                    <span class="list-title">
                                        {{ $fcoi->disclosure_year }} — {{ $fcoi->has_conflict ? 'Conflict declared' : 'No conflict' }}
                                    </span>
                                    <span class="list-sub">{{ $fcoi->proposal?->title ? Str::limit($fcoi->proposal->title, 30) : 'Annual disclosure' }}</span>
                                    @if($fcoi->conflict_description)
                                        <p class="small muted" style="margin-top:7px;">{{ $fcoi->conflict_description }}</p>
                                    @endif
                                    @if($fcoi->management_plan)
                                        <p class="small muted" style="margin-top:5px;"><em>Management plan:</em> {{ $fcoi->management_plan }}</p>
                                    @endif
                                    @if($fcoi->reviewer_notes)
                                        <p class="small muted-3" style="margin-top:5px;">{{ $fcoi->reviewer_notes }}</p>
                                    @endif
                                </div>
                                <x-ui.pill :tone="$fcoi->status === 'cleared' ? 'ok' : ($fcoi->status === 'submitted' ? 'warn' : null)">
                                    {{ ucfirst($fcoi->status) }}
                                </x-ui.pill>
                            </div>

                            @if(auth()->user()->can('review', $fcoi) && in_array($fcoi->status, ['submitted', 'reviewed']))
                                <form method="POST" action="{{ route('fcoi.review', $fcoi) }}" class="card-body" style="background:var(--canvas);">
                                    @csrf @method('PATCH')
                                    <div class="row" style="gap:8px;">
                                        <select name="status" class="select" style="width:150px;">
                                            <option value="reviewed">Mark reviewed</option>
                                            <option value="cleared">Clear</option>
                                        </select>
                                        <input name="reviewer_notes" class="input" style="flex:1;" placeholder="Note (optional)">
                                        <button class="btn btn-sm btn-primary">Record</button>
                                    </div>
                                </form>
                            @endif
                        @empty
                            <x-ui.empty icon="fa-shield-alt">No disclosures on record.</x-ui.empty>
                        @endforelse
                    </div>

                    @if($canEdit)
                        <form method="POST" action="{{ route('researchers.fcoi.store', $user) }}" class="card-body" style="border-top:1px solid var(--line-soft);">
                            @csrf
                            <div class="eyebrow" style="margin-bottom:12px;">New disclosure</div>
                            <div class="form-grid">
                                <x-ui.field label="Year" name="disclosure_year" required>
                                    <input type="number" name="disclosure_year" class="input" value="{{ now()->year }}" min="2000" max="2100" required>
                                </x-ui.field>
                                <x-ui.field label="Any financial interest?" name="has_conflict" required>
                                    <select name="has_conflict" class="select" required
                                            onchange="document.getElementById('conflict-detail').hidden = this.value !== '1'">
                                        <option value="0">No conflict to declare</option>
                                        <option value="1">Yes — I have an interest to declare</option>
                                    </select>
                                </x-ui.field>
                                <div id="conflict-detail" hidden class="field wide">
                                    <label class="label">Description</label>
                                    <textarea name="conflict_description" rows="2" class="textarea"></textarea>
                                    <label class="label" style="margin-top:12px;">Management plan</label>
                                    <textarea name="management_plan" rows="2" class="textarea"></textarea>
                                </div>
                            </div>
                            <div class="form-actions"><button class="btn btn-primary btn-sm">Submit disclosure</button></div>
                        </form>
                    @endif
                </x-ui.card>
            @endif
        </div>
    </div>
</div>
@endsection
