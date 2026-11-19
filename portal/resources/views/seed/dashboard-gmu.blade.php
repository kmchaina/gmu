@extends('layouts.gmu')

@php
    use App\Support\SeedGrant as S;
    $current = $pipeline->search(fn ($p) => $p['count'] > 0 && $p['label'] !== 'Concept note');
    $maxCentre = max(1, $byCentre->max() ?? 1);
@endphp

@section('title', 'Dashboard')
@section('context', 'Seed Grant · FY ' . $call->fiscal_year)

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">{{ $call->title }} · FY {{ $call->fiscal_year }}</p>
        <h1 style="margin-top:6px;">{{ $needs->isEmpty() ? 'Nothing needs you right now' : ($needs->count() === 1 ? 'One submission needs GMU' : $needs->count() . ' submissions need GMU') }}</h1>
        <p class="lede">
            {{ $submittedCount }} submitted{{ $drafts ? ', ' . $drafts . ' still in draft' : '' }}.
            @if($call->concept_deadline) Concept notes {{ $call->concept_deadline->isPast() ? 'closed' : 'close' }} {{ $call->concept_deadline->format('j M Y') }}.@endif
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('seed.ranking') }}" class="btn"><i class="fas fa-list-ol" aria-hidden="true"></i> Ranking</a>
        <a href="{{ route('seed.submissions.index', ['filter' => 'action']) }}" class="btn btn-primary">Work through submissions</a>
    </div>
</div>

<ol aria-label="Applications at each stage" style="list-style:none; margin:0; padding:0; display:flex; flex-wrap:wrap; gap:10px;">
    @foreach($pipeline as $i => $p)
        @php $isNow = $i === $current; @endphp
        <li style="flex:1 1 112px; min-width:0; padding:14px 16px; border-radius:12px; border:1px solid {{ $isNow ? 'var(--brand)' : 'var(--line)' }}; background:{{ $isNow ? 'var(--brand)' : '#fff' }};">
            <div class="mono" style="font-size:22px; {{ $isNow ? 'color:#fff;' : ($p['count'] ? '' : 'color:#94A0B4;') }}">{{ $p['count'] }}</div>
            <div class="small" style="{{ $isNow ? 'color:var(--blue-100);' : 'color:var(--ink-2);' }}">{{ $p['label'] }}</div>
        </li>
    @endforeach
</ol>

<div class="stats" style="margin-top:20px;">
    <div class="stat"><span class="stat-label">Requested in total</span><span class="stat-value">{{ number_format($requested / 1e6, 1) }}M</span><span class="stat-note">TZS across {{ $submittedCount }} · funds {{ $call->envelope ? number_format($call->envelope / 1e6, 1) . 'M' : 'not set' }}</span></div>
    <div class="stat {{ $overdue ? 'is-danger' : '' }}"><span class="stat-label">Reviews overdue</span><span class="stat-value">{{ $overdue }}</span><span class="stat-note">in open rounds</span></div>
    <div class="stat"><span class="stat-label">With applicants</span><span class="stat-value">{{ $withApplicants }}</span><span class="stat-note">revising, writing proposals or accepting agreements</span></div>
    <div class="stat"><span class="stat-label">Not eligible</span><span class="stat-value">{{ $ineligible }}</span><span class="stat-note">at screening</span></div>
</div>

<div class="grid grid-wide" style="margin-top:24px;">
    <section class="card">
        <div class="card-head"><div class="card-title">Needs your action <span class="mono muted">· {{ $needs->count() }}</span></div>
            <a href="{{ route('seed.submissions.index') }}" class="small strong">All submissions</a></div>
        <div class="table-wrap">
            <table class="table" style="min-width:640px;">
                <thead><tr><th>Ref</th><th>Application</th><th>Status</th><th>Waiting</th><th><span class="sr-only">Action</span></th></tr></thead>
                <tbody>
                    @forelse($needs as $n)
                        @php $a = $n['application']; @endphp
                        <tr>
                            <td class="mono sub">{{ $a->reference }}</td>
                            <td><div class="strong">{{ \Illuminate\Support\Str::limit($a->title, 70) }}</div><div class="sub">{{ $a->pi->name }}{{ $a->pi->research_center ? ' · ' . $a->pi->research_center : '' }}</div></td>
                            <td>@include('seed.partials.status', ['application' => $a])</td>
                            <td class="sub" style="white-space:nowrap;">{{ $a->updated_at->diffForHumans(null, true) }}</td>
                            <td style="text-align:right;"><a href="{{ $n['action'] === 'Send to DRCP' ? route('seed.ranking') : route('seed.submissions.show', $a) }}" class="btn btn-sm">{{ $n['action'] }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="table-empty"><i class="fas fa-check-circle" aria-hidden="true"></i>All caught up.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <aside class="stack" style="gap:24px;">
        <section class="card"><div class="card-body">
            <div class="row-between"><h2 class="card-title">Reviewer load</h2><span class="tiny muted">done / assigned, open rounds</span></div>
            <ul style="list-style:none; margin:16px 0 0; padding:0; display:flex; flex-direction:column; gap:14px;">
                @forelse($load as $r)
                    <li>
                        <div class="row-between small"><span class="strong">{{ $r['name'] }} @if($r['late'])<span class="pill pill-danger" style="height:20px; margin-left:4px;">{{ $r['late'] }} late</span>@endif</span><span class="mono">{{ $r['done'] }} / {{ $r['total'] }}</span></div>
                        <div class="meter {{ $r['late'] ? 'danger' : '' }}" style="margin-top:6px;"><span style="width:{{ $r['total'] ? $r['done'] / $r['total'] * 100 : 0 }}%"></span></div>
                    </li>
                @empty
                    <li class="small muted">No open review rounds.</li>
                @endforelse
            </ul>
            <p class="tiny muted" style="margin-top:16px;">Applicants never see reviewer names.</p>
        </div></section>

        <section class="card"><div class="card-body">
            <h2 class="card-title">Submissions by centre</h2>
            <ul style="list-style:none; margin:16px 0 0; padding:0; display:grid; grid-template-columns:auto 1fr auto; gap:10px 12px; align-items:center; font-size:13px;">
                @forelse($byCentre as $centre => $n)
                    <li style="display:contents;"><span>{{ $centre }}</span><span class="meter"><span style="width:{{ $n / $maxCentre * 100 }}%"></span></span><span class="mono">{{ $n }}</span></li>
                @empty
                    <li class="small muted" style="grid-column:1/-1;">No submissions yet.</li>
                @endforelse
            </ul>
        </div></section>
    </aside>
</div>
@endsection
