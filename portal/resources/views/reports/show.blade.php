@extends('layouts.gmu')

@section('title', Str::limit($report->title, 40))
@section('crumb', 'Report')

@section('content')
@php
    use App\Support\Days;
    $days = Days::until($report->due_date);
    $late = $report->status === 'pending' && $days < 0;
@endphp

<div class="page-head">
    <div style="min-width:0;">
        <a href="{{ route('reports.index') }}" class="small">&larr; Reports</a>
        <h1 style="margin-top:8px;">{{ $report->title }}</h1>
        <div class="row" style="gap:12px; margin-top:10px;">
            <x-ui.pill :tone="match(true) {
                $report->status === 'approved' => 'ok',
                $report->status === 'rejected' => 'danger',
                $late => 'danger',
                $report->status === 'under_review' => 'warn',
                default => null }">{{ Str::headline($report->status) }}</x-ui.pill>
            <span class="small muted">{{ ucfirst($report->report_type) }} report · {{ $report->award?->title }}</span>
        </div>
    </div>
    <div class="page-actions">
        @if($report->file_path)
            <a href="{{ route('reports.download', $report) }}" class="btn"><i class="fas fa-download"></i> Download</a>
        @endif
        @can('update', $report)
            <a href="{{ route('reports.edit', $report) }}" class="btn btn-primary"><i class="fas fa-pen"></i> Update report</a>
        @endcan
    </div>
</div>

<div class="stack">
    <div class="stats">
        <x-ui.stat label="Due" :value="$report->due_date?->format('d M Y')"
                   :tone="$late ? 'danger' : ($report->status === 'pending' && $days <= 14 ? 'warn' : null)"
                   :note="$late ? abs($days) . ' days overdue' : ($report->status === 'pending' ? $days . ' days remaining' : 'Filed')" />
        <x-ui.stat label="Period covered"
                   :value="$report->period_start?->format('M Y') . ' – ' . $report->period_end?->format('M Y')"
                   note="As set by the reporting schedule" />
        <x-ui.stat label="Submitted" :value="$report->submitted_date?->format('d M Y') ?? 'Not yet'"
                   :note="$report->submittedBy?->name ?? '—'" />
        <x-ui.stat label="Sent to donor" :value="$report->sent_to_donor_on?->format('d M Y') ?? 'Not sent'"
                   :tone="$report->awaitingDonorSubmission() ? 'warn' : ($report->sent_to_donor_on ? 'ok' : null)"
                   :note="$report->donor_acknowledgement ?: ($report->awaitingDonorSubmission() ? 'Approved internally — still to go out' : '—')" />
    </div>

    <div class="grid grid-wide">
        <div class="stack">
            <x-ui.card title="GMU review">
                @if($report->gmu_comments)
                    <p style="line-height:1.75; color:var(--muted);">{{ $report->gmu_comments }}</p>
                @else
                    <p class="small muted-3">No review comments recorded.</p>
                @endif

                @if($report->verified_at)
                    <div class="divider"></div>
                    <p class="small muted">
                        Verified by {{ $report->verifiedBy?->name }} on {{ $report->verified_at->format('d M Y') }}.
                        @if($report->sent_to_donor_on)
                            Sent to the donor {{ $report->sent_to_donor_on->format('d M Y') }}@if($report->sentBy) by {{ $report->sentBy->name }}@endif.
                        @endif
                    </p>
                @endif
            </x-ui.card>

            @can('view-analytics')
                <x-ui.card title="Quality check" note="Rule-based review of the narrative before it goes to the donor">
                    <p class="small muted" style="line-height:1.7;">
                        Paste the report narrative to check its length, whether it contains quantitative results, and
                        whether it covers objectives, results, challenges and next steps.
                    </p>
                    <textarea id="quality-text" rows="5" class="textarea" style="margin-top:12px;"
                              placeholder="Paste the narrative here"></textarea>
                    <div class="form-actions">
                        <button class="btn btn-primary btn-sm" onclick="checkQuality()">Check quality</button>
                    </div>
                    <div id="quality-result" class="stack-sm" style="margin-top:14px;"></div>
                </x-ui.card>
            @endcan
        </div>

        <div class="stack">
            <x-ui.card title="Report details">
                <div class="stack-sm small">
                    <div class="row-between"><span class="muted">Award</span>
                        <span><a href="{{ route('awards.show', $report->award) }}">{{ Str::limit($report->award?->title, 22) }}</a></span></div>
                    <div class="row-between"><span class="muted">Contract</span><span class="mono">{{ $report->award?->contract_number }}</span></div>
                    <div class="row-between"><span class="muted">Donor</span><span>{{ $report->award?->donor?->acronym ?? $report->award?->donor?->name }}</span></div>
                    <div class="row-between"><span class="muted">Lead PI</span><span>{{ $report->award?->proposal?->leadPi?->name }}</span></div>
                    <div class="row-between"><span class="muted">Type</span><span>{{ ucfirst($report->report_type) }}</span></div>
                </div>

                @if($report->file_path)
                    <a href="{{ route('reports.download', $report) }}" class="btn btn-block" style="margin-top:16px;">
                        <i class="fas fa-file"></i> Download the filed report
                    </a>
                @else
                    <p class="small text-warn" style="margin-top:16px;">No file has been uploaded for this report.</p>
                @endif
            </x-ui.card>
        </div>
    </div>
</div>

@push('scripts')
<script>
function checkQuality() {
    const text = document.getElementById('quality-text').value;
    const out  = document.getElementById('quality-result');

    if (text.trim().length < 50) {
        out.innerHTML = '<div class="alert alert-warn" style="margin:0"><i class="fas fa-exclamation-triangle"></i><span>Paste at least 50 characters to check.</span></div>';
        return;
    }

    out.innerHTML = '<p class="small muted">Checking…</p>';

    fetch('{{ route('ai.report-quality', $report) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ text: text }),
    })
    .then(r => r.json())
    .then(data => {
        let html = '';
        (data.rule_issues || []).forEach(issue => {
            const tone = issue.level === 'high' ? 'error' : (issue.level === 'medium' ? 'warn' : 'info');
            html += '<div class="alert alert-' + tone + '" style="margin:0"><i class="fas fa-circle-info"></i><span>' + issue.message + '</span></div>';
        });
        if (!html) {
            html = '<div class="alert alert-success" style="margin:0"><i class="fas fa-check-circle"></i><span>No issues found by the rule checks.</span></div>';
        }
        if (data.ai_suggestions) {
            html += '<div class="card" style="box-shadow:none"><div class="card-body"><div class="eyebrow" style="margin-bottom:8px">Suggestions</div><p class="small muted" style="line-height:1.7; white-space:pre-line">' + data.ai_suggestions + '</p></div></div>';
        }
        out.innerHTML = html;
    })
    .catch(() => {
        out.innerHTML = '<div class="alert alert-error" style="margin:0"><i class="fas fa-times-circle"></i><span>The check could not be completed. Try again.</span></div>';
    });
}
</script>
@endpush
@endsection
