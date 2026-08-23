@extends('layouts.gmu')

@section('title', $award->contract_number)
@section('crumb', 'Award')

@section('content')
@php
    use App\Support\Days;
    use App\Models\SubAward;
    use App\Models\AwardAmendment;

    $allocated = (float) $award->budgets->sum('allocated_amount');
    $spent     = (float) $award->budgets->sum('spent_amount');
    $burn      = $allocated > 0 ? round(($spent / $allocated) * 100, 1) : 0;
    $ledger    = (float) $award->expenditures->sum('amount');
    $months    = $award->start_date && $award->end_date ? $award->start_date->diffInMonths($award->end_date) : 0;
    $daysLeft  = Days::until($award->end_date);
    $light     = $award->traffic_light;
    $closeout  = $award->closeout;
    $band      = $readiness['band'];
    $bandTone  = match($band) { 'ready' => 'ok', 'minor gaps' => 'info', 'significant gaps' => 'warn', default => 'danger' };
@endphp

<div class="page-head">
    <div style="min-width:0;">
        <a href="{{ route('awards.index') }}" class="small">&larr; Active awards</a>
        <h1 style="margin-top:8px;">{{ $award->title }}</h1>
        <div class="row" style="gap:12px; margin-top:10px;">
            <span class="pill mono">{{ $award->contract_number }}</span>
            <x-ui.pill :tone="$award->status === 'active' ? 'ok' : ($award->status === 'completed' ? null : 'warn')">
                {{ ucfirst($award->status) }}
            </x-ui.pill>
            <span class="small muted">
                {{ $award->donor?->name }} · {{ str_replace('_', ' ', $award->reporting_frequency) }} reporting
            </span>
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('awards.closeout', $award) }}" class="btn"><i class="fas fa-flag-checkered"></i> Closeout checklist</a>
        @can('update', $award)
            <a href="{{ route('awards.edit', $award) }}" class="btn btn-primary"><i class="fas fa-pen"></i> Edit award</a>
        @endcan
    </div>
</div>

<div class="stack">

    {{-- Headline figures --}}
    <div class="stats">
        <x-ui.stat label="Total budget" :value="$award->currency . ' ' . number_format($award->total_budget, 0)"
                   :note="$award->budgets->count() . ' budget ' . Str::plural('line', $award->budgets->count())" />
        <x-ui.stat label="Spent to date" :value="$award->currency . ' ' . number_format($spent, 0)"
                   :tone="$burn > 100 ? 'danger' : ($burn > 85 ? 'warn' : null)"
                   :note="$burn . '% utilised · ' . ($ledger >= $spent && $spent > 0 ? 'traced to ledger' : ($spent > 0 ? 'partly traced' : 'nothing spent'))" />
        <x-ui.stat label="Project period" :value="$months . ' months'"
                   :note="$award->start_date?->format('d M Y') . ' — ' . $award->end_date?->format('d M Y')" />
        <x-ui.stat label="Lead PI" :value="$award->proposal?->leadPi?->name ?? '—'"
                   :note="$award->proposal?->leadPi?->department ?? 'No department recorded'" />
        <x-ui.stat label="Compliance risk" :value="ucfirst($light)"
                   :tone="$light === 'red' ? 'danger' : ($light === 'yellow' ? 'warn' : 'ok')"
                   :note="$daysLeft < 0 ? 'Award ended ' . abs($daysLeft) . 'd ago' : $daysLeft . ' days remaining'" />
        <x-ui.stat label="Audit readiness" :value="$readiness['score'] . '/100'"
                   :tone="$bandTone === 'ok' ? 'ok' : ($bandTone === 'danger' ? 'danger' : ($bandTone === 'warn' ? 'warn' : null))"
                   :note="ucfirst($band)" />
    </div>

    <div class="grid grid-wide">
        <div class="stack">

            {{-- Budget --}}
            <x-ui.card title="Budget" :note="$award->currency . ' ' . number_format($allocated, 0) . ' allocated across ' . $award->budgets->count() . ' ' . Str::plural('line', $award->budgets->count())" :flush="true">
                <x-slot:action>
                    @can('manageBudget', $award)
                        <button class="btn btn-sm" onclick="document.getElementById('add-budget').hidden = !document.getElementById('add-budget').hidden">
                            <i class="fas fa-plus"></i> Budget line
                        </button>
                    @endcan
                </x-slot:action>

                @can('manageBudget', $award)
                    <form id="add-budget" hidden method="POST" action="{{ route('awards.budgets.store', $award) }}"
                          class="card-body" style="background:var(--canvas); border-bottom:1px solid var(--line-soft);">
                        @csrf
                        <div class="form-grid">
                            <x-ui.field label="Category" name="category" required>
                                <input name="category" class="input" placeholder="personnel, equipment, travel…" required>
                            </x-ui.field>
                            <x-ui.field label="Allocated ({{ $award->currency }})" name="allocated_amount" required>
                                <input name="allocated_amount" type="number" step="0.01" min="0" class="input" required>
                            </x-ui.field>
                            <x-ui.field label="Notes" name="notes">
                                <input name="notes" class="input">
                            </x-ui.field>
                        </div>
                        <div class="form-actions"><button class="btn btn-primary btn-sm">Add line</button></div>
                    </form>
                @endcan

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Category</th><th class="num">Allocated</th><th class="num">Spent</th>
                                <th class="num">Remaining</th><th style="width:130px;">Utilisation</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($award->budgets as $line)
                                @php $pct = $line->utilisation(); @endphp
                                <tr>
                                    <td class="strong">{{ ucfirst($line->category) }}</td>
                                    <td class="num">{{ number_format($line->allocated_amount, 0) }}</td>
                                    <td class="num">{{ number_format($line->spent_amount, 0) }}</td>
                                    <td class="num muted">{{ number_format($line->allocated_amount - $line->spent_amount, 0) }}</td>
                                    <td>
                                        <div class="meter-row">
                                            <div class="meter {{ $pct > 100 ? 'danger' : ($pct > 85 ? 'warn' : '') }}"><span style="width: {{ min(100, $pct) }}%"></span></div>
                                            <span class="small" style="font-weight:600; width:36px; text-align:right;">{{ round($pct) }}%</span>
                                        </div>
                                    </td>
                                    <td class="num">
                                        @can('manageBudget', $award)
                                            <form method="POST" action="{{ route('awards.budgets.destroy', [$award, $line]) }}"
                                                  onsubmit="return confirm('Remove this budget line?')" class="inline-form">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-ui.empty icon="fa-coins">No budget lines yet. Add the categories from the donor budget so spend can be tracked against them.</x-ui.empty></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            {{-- Expenditure ledger --}}
            <x-ui.card title="Expenditure ledger" :note="$award->expenditures->count() . ' ' . Str::plural('transaction', $award->expenditures->count()) . ' · what an auditor follows the spend back to'" :flush="true">
                <x-slot:action>
                    @can('manageBudget', $award)
                        <button class="btn btn-sm" onclick="document.getElementById('add-spend').hidden = !document.getElementById('add-spend').hidden">
                            <i class="fas fa-plus"></i> Record
                        </button>
                    @endcan
                </x-slot:action>

                @can('manageBudget', $award)
                    <form id="add-spend" hidden method="POST" action="{{ route('awards.expenditures.store', $award) }}"
                          class="card-body" style="background:var(--canvas); border-bottom:1px solid var(--line-soft);">
                        @csrf
                        <div class="form-grid">
                            <x-ui.field label="Date" name="transaction_date" required>
                                <input type="date" name="transaction_date" class="input" max="{{ now()->toDateString() }}" required>
                            </x-ui.field>
                            <x-ui.field label="Budget line" name="award_budget_id">
                                <select name="award_budget_id" class="select">
                                    <option value="">Unassigned</option>
                                    @foreach($award->budgets as $line)
                                        <option value="{{ $line->id }}">{{ ucfirst($line->category) }}</option>
                                    @endforeach
                                </select>
                            </x-ui.field>
                            <x-ui.field label="Description" name="description" required>
                                <input name="description" class="input" required>
                            </x-ui.field>
                            <x-ui.field label="Amount" name="amount" required>
                                <input name="amount" type="number" step="0.01" min="0.01" class="input" required>
                            </x-ui.field>
                            <x-ui.field label="Voucher reference" name="reference" help="Traceable to the finance record">
                                <input name="reference" class="input">
                            </x-ui.field>
                        </div>
                        <div class="form-actions"><button class="btn btn-primary btn-sm">Post transaction</button></div>
                    </form>
                @endcan

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr><th>Date</th><th>Description</th><th>Budget line</th><th>Reference</th><th class="num">Amount</th><th></th></tr>
                        </thead>
                        <tbody>
                            @forelse($award->expenditures->sortByDesc('transaction_date')->take(12) as $entry)
                                <tr>
                                    <td class="mono small">{{ $entry->transaction_date?->format('d M Y') }}</td>
                                    <td>
                                        {{ $entry->description }}
                                        @if($entry->recordedBy)<div class="tiny muted-3">by {{ $entry->recordedBy->name }}</div>@endif
                                    </td>
                                    <td class="small">{{ $entry->budgetLine ? ucfirst($entry->budgetLine->category) : '—' }}</td>
                                    <td class="small {{ $entry->reference ? 'mono' : 'text-danger' }}">{{ $entry->reference ?: 'none' }}</td>
                                    <td class="num">{{ number_format($entry->amount, 2) }}</td>
                                    <td class="num">
                                        @can('manageBudget', $award)
                                            <form method="POST" action="{{ route('awards.expenditures.destroy', [$award, $entry]) }}"
                                                  onsubmit="return confirm('Remove this entry?')" class="inline-form">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-danger"><i class="fas fa-times"></i></button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-ui.empty icon="fa-receipt">Nothing recorded. Budget lines show what was spent; this is the evidence behind it.</x-ui.empty></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($award->expenditures->count() > 12)
                    <div class="card-foot">Showing the 12 most recent of {{ $award->expenditures->count() }} entries.</div>
                @endif
            </x-ui.card>

            {{-- Reports --}}
            <x-ui.card title="Reports" :note="$award->reports->where('status','approved')->count() . ' approved of ' . $award->reports->count()" :flush="true">
                <x-slot:action>
                    <a href="{{ route('reports.create', ['award_id' => $award->id]) }}" class="btn btn-sm"><i class="fas fa-plus"></i> Add</a>
                </x-slot:action>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Report</th><th>Period</th><th>Due</th><th>Status</th><th>To donor</th></tr></thead>
                        <tbody>
                            @forelse($award->reports->sortBy('due_date') as $report)
                                @php $d = Days::until($report->due_date); $late = $report->status === 'pending' && $d < 0; @endphp
                                <tr>
                                    <td><a href="{{ route('reports.show', $report) }}" class="strong">{{ $report->title }}</a>
                                        <div class="tiny muted-3">{{ ucfirst($report->report_type) }}</div></td>
                                    <td class="small muted">{{ $report->period_start?->format('M Y') }} – {{ $report->period_end?->format('M Y') }}</td>
                                    <td class="small">{{ $report->due_date?->format('d M Y') }}
                                        @if($late)<div class="tiny text-danger">{{ abs($d) }}d overdue</div>@endif</td>
                                    <td><x-ui.pill :tone="$report->status === 'approved' ? 'ok' : ($late ? 'danger' : ($report->status === 'rejected' ? 'danger' : null))">{{ Str::headline($report->status) }}</x-ui.pill></td>
                                    <td class="small">
                                        @if($report->sent_to_donor_on)
                                            <span class="text-ok">{{ $report->sent_to_donor_on->format('d M Y') }}</span>
                                        @elseif($report->status === 'approved')
                                            <span class="text-warn">not sent</span>
                                        @else — @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><x-ui.empty icon="fa-file-invoice">No reports scheduled. The calendar is generated at award setup from the reporting frequency.</x-ui.empty></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            {{-- Sub-awards --}}
            <x-ui.card title="Sub-awards" :note="'Pass-through funding — NIMR stays accountable to the donor for it'" :flush="true">
                <x-slot:action>
                    @can('manageSubAwards', $award)
                        <button class="btn btn-sm" onclick="document.getElementById('add-sub').hidden = !document.getElementById('add-sub').hidden">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    @endcan
                </x-slot:action>

                @can('manageSubAwards', $award)
                    <form id="add-sub" hidden method="POST" action="{{ route('awards.sub-awards.store', $award) }}"
                          enctype="multipart/form-data" class="card-body" style="background:var(--canvas); border-bottom:1px solid var(--line-soft);">
                        @csrf
                        <div class="form-grid">
                            <x-ui.field label="Partner institution" name="partner_name" required>
                                <input name="partner_name" class="input" required>
                            </x-ui.field>
                            <x-ui.field label="Type" name="partner_type">
                                <select name="partner_type" class="select">
                                    @foreach(SubAward::PARTNER_TYPES as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </x-ui.field>
                            <x-ui.field label="Amount ({{ $award->currency }})" name="amount" required>
                                <input name="amount" type="number" step="0.01" min="0" class="input" required>
                            </x-ui.field>
                            <x-ui.field label="Start" name="start_date" required>
                                <input type="date" name="start_date" class="input" required>
                            </x-ui.field>
                            <x-ui.field label="End" name="end_date" required>
                                <input type="date" name="end_date" class="input" required>
                            </x-ui.field>
                            <x-ui.field label="Status" name="status">
                                <select name="status" class="select">
                                    <option value="draft">Draft</option><option value="active">Active</option>
                                    <option value="completed">Completed</option><option value="suspended">Suspended</option>
                                </select>
                            </x-ui.field>
                            <x-ui.field label="Reporting" name="reporting_frequency">
                                <select name="reporting_frequency" class="select">
                                    <option value="quarterly">Quarterly</option><option value="monthly">Monthly</option>
                                    <option value="semi_annual">Every 6 months</option><option value="annual">Annually</option>
                                </select>
                            </x-ui.field>
                            <x-ui.field label="Due diligence completed" name="due_diligence_completed_on">
                                <input type="date" name="due_diligence_completed_on" class="input">
                            </x-ui.field>
                            <x-ui.field label="Signed agreement" name="agreement_file">
                                <input type="file" name="agreement_file" class="input">
                            </x-ui.field>
                        </div>
                        <div class="form-actions"><button class="btn btn-primary btn-sm">Record sub-award</button></div>
                    </form>
                @endcan

                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th></th><th>Partner</th><th class="num">Amount</th><th>Disbursed</th><th>Status</th><th>Due diligence</th><th></th></tr></thead>
                        <tbody>
                            @forelse($award->subAwards as $sub)
                                <tr>
                                    <td><span class="dot dot-{{ $sub->health() === 'red' ? 'red' : ($sub->health() === 'yellow' ? 'amber' : 'green') }}"></span></td>
                                    <td><span class="strong">{{ $sub->partner_name }}</span>
                                        <div class="tiny muted-3">{{ SubAward::PARTNER_TYPES[$sub->partner_type] ?? $sub->partner_type }}@if($sub->country) · {{ $sub->country }}@endif</div></td>
                                    <td class="num">{{ $sub->currency }} {{ number_format($sub->amount, 0) }}</td>
                                    <td class="small">{{ number_format($sub->disbursedPercentage(), 0) }}%</td>
                                    <td><x-ui.pill :tone="$sub->status === 'active' ? 'ok' : null">{{ ucfirst($sub->status) }}</x-ui.pill></td>
                                    <td class="small">
                                        @if($sub->due_diligence_completed_on)
                                            <span class="text-ok">{{ $sub->due_diligence_completed_on->format('M Y') }}</span>
                                        @else<span class="text-danger">not done</span>@endif
                                    </td>
                                    <td class="num">
                                        @if($sub->agreement_file)
                                            <a href="{{ route('awards.sub-awards.agreement', [$award, $sub]) }}" class="btn btn-sm" title="Signed agreement"><i class="fas fa-file-contract"></i></a>
                                        @endif
                                        @can('manageSubAwards', $award)
                                            <form method="POST" action="{{ route('awards.sub-awards.destroy', [$award, $sub]) }}"
                                                  onsubmit="return confirm('Remove this sub-award?')" class="inline-form">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7"><x-ui.empty icon="fa-handshake">No sub-awards. Money passed to a partner institution belongs here — the accountability stays with NIMR.</x-ui.empty></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            {{-- Compliance calendar --}}
            <x-ui.card title="Compliance calendar" note="Deliverables other than reports: briefings, visits, closeout" :flush="true">
                <x-slot:action>
                    @can('manageCompliance', $award)
                        <button class="btn btn-sm" onclick="document.getElementById('add-comp').hidden = !document.getElementById('add-comp').hidden">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    @endcan
                </x-slot:action>

                @can('manageCompliance', $award)
                    <form id="add-comp" hidden method="POST" action="{{ route('awards.compliance.store', $award) }}"
                          class="card-body" style="background:var(--canvas); border-bottom:1px solid var(--line-soft);">
                        @csrf
                        <div class="form-grid">
                            <x-ui.field label="Title" name="title" required><input name="title" class="input" required></x-ui.field>
                            <x-ui.field label="Type" name="deliverable_type" required>
                                <select name="deliverable_type" class="select">
                                    @foreach(['technical_report','financial_report','ethics_renewal','audit','site_visit','other'] as $t)
                                        <option value="{{ $t }}">{{ Str::headline($t) }}</option>
                                    @endforeach
                                </select>
                            </x-ui.field>
                            <x-ui.field label="Due date" name="due_date" required><input type="date" name="due_date" class="input" required></x-ui.field>
                        </div>
                        <div class="form-actions"><button class="btn btn-primary btn-sm">Add deliverable</button></div>
                    </form>
                @endcan

                <div class="list">
                    @forelse($award->complianceCalendar->sortBy('due_date') as $item)
                        @php $d = Days::until($item->due_date); @endphp
                        <div class="list-row">
                            <div class="list-main">
                                <span class="list-title">{{ $item->title }}</span>
                                <span class="list-sub">{{ Str::headline($item->deliverable_type) }} · due {{ $item->due_date?->format('d M Y') }}</span>
                            </div>
                            <x-ui.pill :tone="$item->status === 'submitted' ? 'ok' : ($item->status === 'overdue' || $d < 0 ? 'danger' : ($d <= 14 ? 'warn' : null))">
                                {{ $item->status === 'submitted' ? 'Done' : ($d < 0 ? abs($d) . 'd overdue' : $d . 'd') }}
                            </x-ui.pill>
                            @can('manageCompliance', $award)
                                @if($item->status !== 'submitted')
                                    <form method="POST" action="{{ route('awards.compliance.update', [$award, $item]) }}" class="inline-form">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="submitted">
                                        <button class="btn btn-sm">Mark done</button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    @empty
                        <x-ui.empty icon="fa-calendar-check">Nothing scheduled beyond the reports.</x-ui.empty>
                    @endforelse
                </div>
            </x-ui.card>

            {{-- Amendments --}}
            <x-ui.card title="Amendments" note="Extensions, budget revisions and scope changes, with the donor approval behind them" :flush="true">
                <x-slot:action>
                    @can('update', $award)
                        <button class="btn btn-sm" onclick="document.getElementById('add-amd').hidden = !document.getElementById('add-amd').hidden">
                            <i class="fas fa-plus"></i> Record
                        </button>
                    @endcan
                </x-slot:action>

                @can('update', $award)
                    <form id="add-amd" hidden method="POST" action="{{ route('awards.amendments.store', $award) }}"
                          enctype="multipart/form-data" class="card-body" style="background:var(--canvas); border-bottom:1px solid var(--line-soft);">
                        @csrf
                        <div class="form-grid">
                            <x-ui.field label="Type" name="type">
                                <select name="type" class="select">
                                    @foreach(AwardAmendment::TYPES as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </x-ui.field>
                            <x-ui.field label="New end date" name="new_end_date"><input type="date" name="new_end_date" class="input"></x-ui.field>
                            <x-ui.field label="New value ({{ $award->currency }})" name="new_budget">
                                <input type="number" step="0.01" min="0" name="new_budget" class="input">
                            </x-ui.field>
                            <x-ui.field label="Donor reference" name="reference"><input name="reference" class="input"></x-ui.field>
                            <x-ui.field label="Reason" name="reason" required wide>
                                <input name="reason" class="input" required placeholder="Why the change was sought">
                            </x-ui.field>
                            <x-ui.field label="Status" name="status">
                                <select name="status" class="select">
                                    <option value="draft">Draft</option><option value="submitted">Submitted</option>
                                    <option value="approved">Approved by donor</option><option value="rejected">Rejected</option>
                                </select>
                            </x-ui.field>
                            <x-ui.field label="Approved on" name="donor_approved_on"><input type="date" name="donor_approved_on" class="input"></x-ui.field>
                            <x-ui.field label="Approval letter" name="approval_file"><input type="file" name="approval_file" class="input"></x-ui.field>
                        </div>
                        <p class="help">Recording an approved amendment applies it to the award; an extension regenerates the reporting calendar.</p>
                        <div class="form-actions"><button class="btn btn-primary btn-sm">Record amendment</button></div>
                    </form>
                @endcan

                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Type</th><th>Reason</th><th>Effect</th><th>Status</th><th>Approved</th><th></th></tr></thead>
                        <tbody>
                            @forelse($award->amendments as $amendment)
                                <tr>
                                    <td class="strong">{{ $amendment->typeName() }}
                                        @if($amendment->reference)<div class="tiny mono muted-3">{{ $amendment->reference }}</div>@endif</td>
                                    <td class="small muted">{{ Str::limit($amendment->reason, 54) }}</td>
                                    <td class="small">
                                        @php $days = $amendment->daysExtended(); $delta = $amendment->budgetDelta(); @endphp
                                        @if($days)<div>{{ $days > 0 ? '+' : '' }}{{ $days }} days</div>@endif
                                        @if($delta !== null && $delta != 0)<div class="mono">{{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 0) }}</div>@endif
                                        @if(! $days && ($delta === null || $delta == 0))—@endif
                                    </td>
                                    <td><x-ui.pill :tone="$amendment->status === 'approved' ? 'ok' : ($amendment->status === 'rejected' ? 'danger' : null)">{{ ucfirst($amendment->status) }}</x-ui.pill></td>
                                    <td class="small">{{ $amendment->donor_approved_on?->format('d M Y') ?? '—' }}</td>
                                    <td class="num">
                                        @if($amendment->approval_file)
                                            <a href="{{ route('awards.amendments.approval', [$award, $amendment]) }}" class="btn btn-sm"><i class="fas fa-file"></i></a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-ui.empty icon="fa-file-signature">No amendments. Extensions and budget revisions belong here so the award shows its history, not just its current state.</x-ui.empty></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>

        {{-- Right column --}}
        <div class="stack">

            <x-ui.card title="Audit readiness" :note="'What an auditor would ask about'">
                <div class="row-between" style="align-items:baseline;">
                    <span style="font-size:40px; font-weight:600; letter-spacing:-1.5px; line-height:1;
                                 color: var(--{{ $bandTone === 'ok' ? 'ok-ink' : ($bandTone === 'danger' ? 'danger-ink' : ($bandTone === 'warn' ? 'warn-ink' : 'navy-500')) }});">
                        {{ $readiness['score'] }}
                    </span>
                    <x-ui.pill :tone="$bandTone">{{ ucfirst($band) }}</x-ui.pill>
                </div>
                <div class="meter {{ $readiness['score'] < 45 ? 'danger' : ($readiness['score'] < 65 ? 'warn' : '') }}" style="margin:14px 0 18px;">
                    <span style="width: {{ $readiness['score'] }}%"></span>
                </div>

                @foreach($readiness['dimensions'] as $dimension)
                    <div class="row-between small" style="padding:5px 0;">
                        <span class="muted">{{ $dimension['label'] }}</span>
                        <span class="mono">{{ $dimension['earned'] }} / {{ $dimension['weight'] }}</span>
                    </div>
                @endforeach

                @if(count($readiness['gaps']))
                    <div class="divider"></div>
                    <div class="small muted" style="margin-bottom:8px;">Gaps to close</div>
                    <ul class="small muted" style="margin:0; padding-left:18px; line-height:1.7;">
                        @foreach($readiness['gaps'] as $gap)<li>{{ $gap }}</li>@endforeach
                    </ul>
                @endif
            </x-ui.card>

            <x-ui.card title="Closeout">
                @if($closeout && $closeout->status === 'completed')
                    <x-ui.pill tone="ok">Closed out</x-ui.pill>
                    <p class="small muted" style="margin-top:10px;">
                        Signed off {{ $closeout->completed_at?->format('d M Y') }} by {{ $closeout->completedBy?->name }}.
                        @if($closeout->archive_reference)<br>Archive: <span class="mono">{{ $closeout->archive_reference }}</span>@endif
                    </p>
                @elseif($closeout && $closeout->progress() > 0)
                    <div class="meter"><span style="width: {{ $closeout->progress() }}%"></span></div>
                    <p class="small muted" style="margin-top:10px;">
                        {{ $closeout->progress() }}% complete · {{ count($closeout->outstandingSteps()) }} {{ Str::plural('step', count($closeout->outstandingSteps())) }} outstanding.
                    </p>
                @elseif($daysLeft <= 60)
                    <p class="small muted">The award ends {{ Days::describe($award->end_date) }}. Closeout should begin now.</p>
                @else
                    <p class="small muted">Not started — the award is still running.</p>
                @endif
                <a href="{{ route('awards.closeout', $award) }}" class="btn btn-block" style="margin-top:14px;">Open closeout checklist</a>
            </x-ui.card>

            <x-ui.card title="Ethics &amp; protocols" :flush="true">
                <x-slot:action>
                    @can('manageEthics', $award)
                        <button class="btn btn-sm" onclick="document.getElementById('add-eth').hidden = !document.getElementById('add-eth').hidden"><i class="fas fa-plus"></i></button>
                    @endcan
                </x-slot:action>

                @can('manageEthics', $award)
                    <form id="add-eth" hidden method="POST" action="{{ route('awards.ethics.store', $award) }}"
                          enctype="multipart/form-data" class="card-body" style="background:var(--canvas); border-bottom:1px solid var(--line-soft);">
                        @csrf
                        <x-ui.field label="Ethics number" name="ethics_number" required><input name="ethics_number" class="input" required></x-ui.field>
                        <x-ui.field label="Issuing body" name="issuing_body"><input name="issuing_body" class="input" value="NIMR Medical Research Coordinating Committee"></x-ui.field>
                        <x-ui.field label="Expiry" name="expiry_date" required><input type="date" name="expiry_date" class="input" required></x-ui.field>
                        <input type="hidden" name="renewal_status" value="valid">
                        <x-ui.field label="Certificate" name="certificate_file"><input type="file" name="certificate_file" class="input"></x-ui.field>
                        <div class="form-actions"><button class="btn btn-primary btn-sm">Add</button></div>
                    </form>
                @endcan

                <div class="list">
                    @forelse($award->ethicsRenewals as $renewal)
                        @php $d = Days::until($renewal->expiry_date); @endphp
                        <div class="list-row">
                            <div class="list-main">
                                <span class="list-title mono">{{ $renewal->ethics_number }}</span>
                                <span class="list-sub">Expires {{ $renewal->expiry_date?->format('d M Y') }}</span>
                            </div>
                            <x-ui.pill :tone="$d < 0 ? 'danger' : ($d <= 60 ? 'warn' : 'ok')">
                                {{ $d < 0 ? 'Expired' : $d . 'd' }}
                            </x-ui.pill>
                            @if($renewal->certificate_file)
                                <a href="{{ route('awards.ethics.certificate', [$award, $renewal]) }}" class="btn btn-sm"><i class="fas fa-certificate"></i></a>
                            @endif
                        </div>
                    @empty
                        <x-ui.empty icon="fa-shield-alt">No protocol recorded. An auditor will ask for this.</x-ui.empty>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Monitoring visits" :flush="true">
                <x-slot:action>
                    @can('manageMonitoring', $award)
                        <button class="btn btn-sm" onclick="document.getElementById('add-vis').hidden = !document.getElementById('add-vis').hidden"><i class="fas fa-plus"></i></button>
                    @endcan
                </x-slot:action>

                @can('manageMonitoring', $award)
                    <form id="add-vis" hidden method="POST" action="{{ route('awards.monitoring.store', $award) }}"
                          enctype="multipart/form-data" class="card-body" style="background:var(--canvas); border-bottom:1px solid var(--line-soft);">
                        @csrf
                        <x-ui.field label="Visit date" name="visit_date" required><input type="date" name="visit_date" class="input" required></x-ui.field>
                        <x-ui.field label="Location" name="location"><input name="location" class="input"></x-ui.field>
                        <x-ui.field label="Findings" name="findings" required help="At least 20 characters">
                            <textarea name="findings" rows="3" class="textarea" required></textarea>
                        </x-ui.field>
                        <x-ui.field label="Recommendations" name="recommendations"><textarea name="recommendations" rows="2" class="textarea"></textarea></x-ui.field>
                        <div class="form-actions"><button class="btn btn-primary btn-sm">Log visit</button></div>
                    </form>
                @endcan

                <div class="list">
                    @forelse($award->monitoringVisits->sortByDesc('visit_date') as $visit)
                        <div class="list-row" style="align-items:flex-start;">
                            <div class="list-main">
                                <span class="list-title">{{ $visit->visit_date?->format('d M Y') }}@if($visit->location) · {{ $visit->location }}@endif</span>
                                <span class="list-sub">{{ Str::limit($visit->findings, 90) }}</span>
                            </div>
                            @if($visit->report_file)
                                <a href="{{ route('awards.monitoring.report', [$award, $visit]) }}" class="btn btn-sm"><i class="fas fa-file"></i></a>
                            @endif
                        </div>
                    @empty
                        <x-ui.empty icon="fa-binoculars">No visits logged.</x-ui.empty>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Award details">
                <div class="stack-sm">
                    <div class="row-between"><span class="small muted">Contract</span><span class="mono small">{{ $award->contract_number }}</span></div>
                    <div class="row-between"><span class="small muted">Donor</span><span class="small">{{ $award->donor?->name }}</span></div>
                    <div class="row-between"><span class="small muted">Proposal</span>
                        <span class="small">
                            @if($award->proposal)
                                <a href="{{ route('proposals.show', $award->proposal) }}">{{ Str::limit($award->proposal->title, 24) }}</a>
                            @else — @endif
                        </span>
                    </div>
                    <div class="row-between"><span class="small muted">Kickoff</span><span class="small">{{ $award->kickoff_date?->format('d M Y') ?? 'Not recorded' }}</span></div>
                    <div class="row-between"><span class="small muted">Reporting</span><span class="small">{{ Str::headline($award->reporting_frequency) }}</span></div>
                    @if($award->signed_contract)
                        <a href="{{ route('awards.contract', $award) }}" class="btn btn-sm btn-block" style="margin-top:6px;">
                            <i class="fas fa-file-contract"></i> Signed contract
                        </a>
                    @else
                        <p class="small text-warn" style="margin-top:6px;">No signed contract on file.</p>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </div>
</div>
@endsection
