@extends('layouts.gmu')

@section('title', 'Portfolio analytics')
@section('crumb', 'Management')

@section('content')
@php
    $base = $kpis['base_currency'];
    $readinessTone = $kpis['audit_readiness'] >= 85 ? 'ok' : ($kpis['audit_readiness'] >= 65 ? null : ($kpis['audit_readiness'] >= 45 ? 'warn' : 'danger'));
@endphp

<x-ui.page-head
    title="Portfolio analytics"
    :lede="'Everything converted to ' . $base . ' at the rate each award was booked at · ' . now()->format('j F Y')">
    <x-slot:actions>
        <button class="btn" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    </x-slot:actions>
</x-ui.page-head>

<div class="stack">

    <div class="stats">
        <x-ui.stat label="Portfolio under management" :value="$base . ' ' . number_format($kpis['total_funding'])"
                   :note="$kpis['active_awards'] . ' active ' . Str::plural('award', $kpis['active_awards'])" />
        <x-ui.stat label="Success rate" :value="$kpis['success_rate'] . '%'"
                   :note="'of ' . $kpis['success_rate_basis'] . ' proposals the donor has decided'" />
        <x-ui.stat label="Audit readiness" :value="$kpis['audit_readiness'] . '/100'" :tone="$readinessTone"
                   note="Across active and completed awards" />
        <x-ui.stat label="Sub-award exposure" :value="$base . ' ' . number_format($kpis['sub_award_exposure'])"
                   :note="$kpis['open_sub_awards'] . ' open ' . Str::plural('sub-award', $kpis['open_sub_awards'])" />
        <x-ui.stat label="Overdue reports" :value="$kpis['overdue_reports']"
                   :tone="$kpis['overdue_reports'] > 0 ? 'danger' : 'ok'"
                   :note="$kpis['compliance_overdue'] . ' compliance items overdue'" />
        <x-ui.stat label="Awaiting closeout" :value="$kpis['awaiting_closeout']"
                   :tone="$kpis['awaiting_closeout'] > 0 ? 'warn' : 'ok'"
                   :note="$kpis['ethics_expiring_soon'] . ' ethics approvals expiring in 60d'" />
    </div>

    <div class="grid grid-2">
        <x-ui.card title="Proposals by stage" note="Where the pipeline is sitting today">
            @php $maxProposals = max(1, $proposalsByStatus->max() ?? 1); @endphp
            <div class="stack-sm">
                @forelse($proposalsByStatus as $status => $count)
                    <div>
                        <div class="row-between small" style="margin-bottom:6px;">
                            <span>{{ Str::headline($status) }}</span>
                            <span class="mono">{{ $count }}</span>
                        </div>
                        <div class="meter"><span style="width: {{ ($count / $maxProposals) * 100 }}%"></span></div>
                    </div>
                @empty
                    <p class="small muted">No proposals recorded yet.</p>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card title="Awards by status">
            @php $maxAwards = max(1, $awardsByStatus->max() ?? 1); @endphp
            <div class="stack-sm">
                @forelse($awardsByStatus as $status => $count)
                    <div>
                        <div class="row-between small" style="margin-bottom:6px;">
                            <span>{{ ucfirst($status) }}</span>
                            <span class="mono">{{ $count }}</span>
                        </div>
                        <div class="meter {{ in_array($status, ['suspended','terminated']) ? 'danger' : '' }}">
                            <span style="width: {{ ($count / $maxAwards) * 100 }}%"></span>
                        </div>
                    </div>
                @empty
                    <p class="small muted">No awards recorded yet.</p>
                @endforelse
            </div>
        </x-ui.card>
    </div>

    <div class="grid grid-2">
        <x-ui.card title="Funding by donor" :note="'Top funders, in ' . $base">
            @php $maxDonor = max(1, collect($fundingByDonor)->max('amount') ?: 1); @endphp
            <div class="stack-sm">
                @forelse($fundingByDonor as $row)
                    <div>
                        <div class="row-between small" style="margin-bottom:6px;">
                            <span>{{ $row['label'] }}</span>
                            <span class="mono">{{ number_format($row['amount']) }}</span>
                        </div>
                        <div class="meter"><span style="width: {{ ($row['amount'] / $maxDonor) * 100 }}%"></span></div>
                    </div>
                @empty
                    <p class="small muted">No awards to summarise yet.</p>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card title="Reporting timeliness" note="Submitted reports over the last 12 months">
            @php
                $total  = max(1, $reportTimeliness['total']);
                $onTime = $reportTimeliness['on_time'];
                $pct    = round(($onTime / $total) * 100);
            @endphp
            @if($reportTimeliness['total'] === 0)
                <p class="small muted">No reports have been submitted in the last 12 months.</p>
            @else
                <div class="row-between" style="align-items:baseline;">
                    <span style="font-size:34px; font-weight:600; letter-spacing:-1.2px;">{{ $pct }}%</span>
                    <span class="small muted">on time</span>
                </div>
                <div class="meter {{ $pct < 60 ? 'danger' : ($pct < 85 ? 'warn' : '') }}" style="margin:14px 0 16px;">
                    <span style="width: {{ $pct }}%"></span>
                </div>
                <div class="row-between small">
                    <span class="muted">On time <b class="mono">{{ $onTime }}</b></span>
                    <span class="muted">Late <b class="mono">{{ $reportTimeliness['late'] }}</b></span>
                    <span class="muted">Total <b class="mono">{{ $reportTimeliness['total'] }}</b></span>
                </div>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card title="Budget utilisation" note="Active awards, converted to the reporting currency" :flush="true">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Award</th><th class="num">Allocated</th><th class="num">Spent</th><th class="num">Remaining</th><th style="width:150px;">Utilisation</th></tr>
                </thead>
                <tbody>
                    @forelse($budgetUtilisation as $row)
                        <tr>
                            <td class="strong mono">{{ $row['label'] }}</td>
                            <td class="num">{{ number_format($row['allocated'], 0) }}</td>
                            <td class="num">{{ number_format($row['spent'], 0) }}</td>
                            <td class="num muted">{{ number_format($row['allocated'] - $row['spent'], 0) }}</td>
                            <td>
                                <div class="meter-row">
                                    <div class="meter {{ $row['light'] === 'red' ? 'danger' : ($row['light'] === 'yellow' ? 'warn' : '') }}">
                                        <span style="width: {{ min(100, $row['pct']) }}%"></span>
                                    </div>
                                    <span class="small" style="font-weight:600; width:40px; text-align:right;">{{ $row['pct'] }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-ui.empty icon="fa-coins">No active awards with budget lines.</x-ui.empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div class="grid grid-2">
        <x-ui.card title="Awards started per year">
            @php $maxYear = max(1, $awardsPerYear->max() ?? 1); @endphp
            <div class="stack-sm">
                @forelse($awardsPerYear as $year => $count)
                    <div>
                        <div class="row-between small" style="margin-bottom:6px;">
                            <span class="mono">{{ $year }}</span><span class="mono">{{ $count }}</span>
                        </div>
                        <div class="meter"><span style="width: {{ ($count / $maxYear) * 100 }}%"></span></div>
                    </div>
                @empty
                    <p class="small muted">No awards recorded yet.</p>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card title="Recent activity" note="From the audit trail" :flush="true">
            <div class="list">
                @forelse($recentActivity as $log)
                    <div class="list-row">
                        <div class="list-main">
                            <span class="list-title">{{ $log->description }}</span>
                            <span class="list-sub">{{ $log->user?->name ?? 'System' }} · {{ $log->created_at?->diffForHumans() }}</span>
                        </div>
                        <x-ui.pill :tone="$log->action === 'deleted' ? 'danger' : ($log->action === 'created' ? 'ok' : null)">
                            {{ ucfirst($log->action) }}
                        </x-ui.pill>
                    </div>
                @empty
                    <x-ui.empty icon="fa-history">Nothing recorded yet.</x-ui.empty>
                @endforelse
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
