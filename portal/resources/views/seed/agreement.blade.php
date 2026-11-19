@extends('layouts.gmu')

@php
    use App\Support\SeedGrant as S;
    $pi = $application->pi;
    $call = $application->call;
    $isPi = auth()->id() === $application->pi_id;
    $dg = $application->approvalAt('dg');
@endphp

@section('title', 'Grant agreement')
@section('context', 'Grant agreement · ' . $application->reference)

@section('content')
<div class="row-between no-print" style="margin-bottom:20px;">
    <div class="row" style="gap:10px;">
        <h1 style="font-size:20px;">Grant agreement</h1>
        <span class="mono small muted">{{ $application->reference }}</span>
        @include('seed.partials.status')
    </div>
    <div class="row">
        <button type="button" class="btn" onclick="window.print()"><i class="fas fa-print" aria-hidden="true"></i> Print or save as PDF</button>
        @if($application->signed_agreement_path)
            <a href="{{ route('seed.applications.agreement.signed', $application) }}" class="btn"><i class="fas fa-download" aria-hidden="true"></i> Signed copy</a>
        @endif
    </div>
</div>

<div class="grid grid-wide" style="align-items:start;">
    <article class="paper">
        <div class="row-between" style="align-items:flex-start; padding-bottom:20px; border-bottom:3px solid var(--brand);">
            <div class="row" style="gap:14px; flex-wrap:nowrap;">
                <img src="{{ asset('images/nimr-logo.png') }}" alt="" style="width:56px; height:56px; object-fit:contain;">
                <div>
                    <div style="font-size:12px; font-weight:600; letter-spacing:.08em; text-transform:uppercase;">National Institute for Medical Research</div>
                    <div class="tiny muted" style="margin-top:2px;">Directorate of Research Coordination and Promotion · Grants Management Unit</div>
                </div>
            </div>
            <div class="mono tiny" style="text-align:right; color:var(--ink-2);">Ref. {{ $application->reference }}<br>Issued {{ $application->agreement_issued_at->format('j M Y') }}</div>
        </div>

        <h1 style="margin-top:28px; font-size:24px; letter-spacing:-0.02em;">Seed Grant Agreement</h1>
        <p class="muted" style="margin-top:4px;">{{ $call->title }}, FY {{ $call->fiscal_year }}</p>

        <h2>1. Parties</h2>
        <p>This agreement is between the National Institute for Medical Research (“NIMR”), acting through the Grants Management Unit, and <strong>{{ $pi->name }}</strong>{{ $pi->designation ? ', ' . $pi->designation : '' }}{{ $pi->research_center ? ', ' . $pi->research_center : '' }} (“the Principal Investigator”).</p>

        <h2>2. Project</h2>
        <p><strong>{{ $application->title }}</strong>, as described in the full proposal approved by the Director General, which forms part of this agreement.@if($application->mentor_name) Scientific mentor: {{ $application->mentor_name }}.@endif</p>

        <h2>3. Award</h2>
        <p>NIMR awards <strong class="mono">{{ S::tzs($application->approved_amount) }}</strong> against the approved budget below. Funds are released in instalments linked to the approved workplan and milestones, in line with NIMR financial procedures.</p>
        <table class="ptable">
            <thead><tr><th>Budget category</th>@foreach(['Q1', 'Q2', 'Q3', 'Q4'] as $q)<th style="text-align:right;">{{ $q }}</th>@endforeach<th style="text-align:right;">Total (TZS)</th></tr></thead>
            <tbody>
                @foreach(S::BUDGET_CATEGORIES as $key => $label)
                    @php $lines = $application->budgetLines('proposal')->where('category', $key); @endphp
                    @if($lines->isNotEmpty())
                        <tr><td>{{ $label }}</td>
                            @foreach(['q1', 'q2', 'q3', 'q4'] as $q)<td class="mono" style="text-align:right;">{{ number_format($lines->sum($q)) }}</td>@endforeach
                            <td class="mono" style="text-align:right;">{{ number_format($lines->sum(fn ($l) => $l->total())) }}</td></tr>
                    @endif
                @endforeach
                <tr><td><strong>Total</strong></td>@foreach($application->budgetByQuarter() as $v)<td class="mono" style="text-align:right;"><strong>{{ number_format($v) }}</strong></td>@endforeach
                    <td class="mono" style="text-align:right;"><strong>{{ number_format($application->budgetTotal('proposal')) }}</strong></td></tr>
            </tbody>
        </table>

        <h2>4. Period and milestones</h2>
        <p>{{ $call->duration_months }} months from the date funds are first released. Milestones are those in the approved workplan:</p>
        <p class="doc-text" style="font-size:14px;">{{ $application->sectionText('proposal', 'workplan') }}</p>

        <h2>5. Reporting</h2>
        <p>The Principal Investigator submits a progress report and a financial report every quarter, and at the end a final technical report, a dissemination output and a manuscript prepared for publication where feasible.</p>

        <h2>6. Ethics and approvals</h2>
        <p>Required ethical approvals, regulatory permissions and site authorisations must be in place before any activity that needs them. No human-participant or animal research may begin without them.</p>

        @if($application->approval_conditions)
            <h2>7. Conditions set at approval</h2>
            <p class="doc-text" style="font-size:14px;">{{ $application->approval_conditions }}</p>
        @endif

        <h2>{{ $application->approval_conditions ? 8 : 7 }}. Changes, unspent funds and termination</h2>
        <p>Moving money between budget lines, or changing scope, personnel, sites or timelines, needs prior written approval from GMU and the relevant authorised NIMR office. Unspent or ineligible funds are handled under NIMR financial regulations. Missing milestones or reports without satisfactory justification may lead to suspension of funding or termination. The award is not renewable.</p>

        <div style="margin-top:48px; display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:32px 40px;">
            <div>
                <div class="mono tiny" style="height:40px; border-bottom:1px solid var(--ink); display:flex; align-items:flex-end; padding-bottom:6px; color:{{ $application->agreement_accepted_at ? 'var(--blue-700)' : 'var(--danger-ink)' }};">
                    {{ $application->agreement_accepted_at ? 'Accepted online ' . $application->agreement_accepted_at->format('j M Y H:i') : 'awaiting acceptance' }}</div>
                <div class="small" style="margin-top:6px;"><strong>Principal Investigator</strong><br><span class="muted">{{ $pi->name }}</span></div>
            </div>
            <div><div style="height:40px; border-bottom:1px solid var(--ink);"></div><div class="small" style="margin-top:6px;"><strong>GMU Coordinator</strong><br><span class="muted">For NIMR</span></div></div>
            <div><div style="height:40px; border-bottom:1px solid var(--ink);"></div><div class="small" style="margin-top:6px;"><strong>Director, DRCP</strong></div></div>
            <div><div style="height:40px; border-bottom:1px solid var(--ink);"></div><div class="small" style="margin-top:6px;"><strong>Director General</strong>@if($dg)<br><span class="muted">Approved {{ $dg->created_at->format('j M Y') }}</span>@endif</div></div>
        </div>
    </article>

    <aside class="stack-sm no-print" style="gap:16px;">
        @if($isPi && $application->status === S::AGREEMENT_ISSUED)
            <form method="POST" action="{{ route('seed.applications.agreement.accept', $application) }}" class="card">
                @csrf
                <div class="card-body">
                    <h2 class="card-title" style="font-size:17px;">Accept the agreement</h2>
                    <p class="small muted" style="margin-top:4px;">Accepting here is your signature as PI. GMU then collects the other signatures.</p>
                    <div style="margin-top:12px;" class="stack-sm">
                        <label class="check" style="align-items:flex-start;"><input type="checkbox" name="read" value="1" style="margin-top:2px;">I have read the whole agreement, including the approved proposal and budget.</label>
                        <label class="check" style="align-items:flex-start;"><input type="checkbox" name="ethics" value="1" style="margin-top:2px;">No data collection will start before ethics approval.</label>
                        <label class="check" style="align-items:flex-start;"><input type="checkbox" name="reporting" value="1" style="margin-top:2px;">I will submit quarterly progress and financial reports.</label>
                    </div>
                    <button class="btn btn-primary btn-block" style="margin-top:14px;" type="submit">Accept as Principal Investigator</button>
                </div>
            </form>
        @endif

        @can('manage-seed-grants')
            @if(in_array($application->status, [S::AGREEMENT_ACCEPTED, S::AWARDED], true))
                <a href="{{ route('seed.submissions.show', $application) }}" class="btn btn-block">{{ $application->status === S::AWARDED ? 'Manage the signed copy' : 'Upload the signed copy' }}</a>
            @endif
        @endcan

        <section class="card"><div class="card-body">
            <h2 class="card-title" style="font-size:15px;">Where it stands</h2>
            <ol style="list-style:none; margin:14px 0 0; padding:0; display:flex; flex-direction:column; gap:14px; font-size:13px;">
                @foreach([
                    ['Approved by the Director General', (bool) $dg],
                    ['Agreement issued', (bool) $application->agreement_issued_at],
                    ['Accepted online by the PI', (bool) $application->agreement_accepted_at],
                    ['Signed copy on file', (bool) $application->signed_agreement_uploaded_at],
                ] as [$label, $done])
                    <li class="row" style="gap:10px; flex-wrap:nowrap;">
                        <span style="flex:0 0 18px; width:18px; height:18px; border-radius:50%; display:grid; place-items:center; {{ $done ? 'background:var(--brand); color:#fff;' : 'border:2px solid var(--line-2);' }}">@if($done)<i class="fas fa-check" style="font-size:9px;" aria-hidden="true"></i>@endif</span>
                        <span class="{{ $done ? '' : 'muted' }}">{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
        </div></section>
    </aside>
</div>
@endsection
