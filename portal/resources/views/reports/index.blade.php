@extends('layouts.gmu')

@section('title', 'Reports')
@section('crumb', 'Awards & compliance')

@section('content')
@php
    use App\Support\Days;
    $current = request('status');
    $overdue = $reports->filter(fn($r) => $r->status === 'pending' && Days::isPast($r->due_date))->count();
@endphp

<x-ui.page-head
    title="Reports"
    :lede="$reports->total() . ' ' . Str::plural('report', $reports->total()) . ' on record' . ($overdue ? ' · ' . $overdue . ' overdue on this page' : '')">
    <x-slot:actions>
        @can('create', App\Models\Report::class)
            <a href="{{ route('reports.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Record a report</a>
        @endcan
    </x-slot:actions>
</x-ui.page-head>

<div class="stack">

    <div class="row-between">
        <div class="chips">
            <a href="{{ route('reports.index') }}" class="chip {{ ! $current ? 'is-active' : '' }}">All</a>
            @foreach(['pending' => 'Pending', 'submitted' => 'Submitted', 'under_review' => 'Under review', 'approved' => 'Approved', 'rejected' => 'Returned'] as $value => $label)
                <a href="{{ route('reports.index', ['status' => $value]) }}" class="chip {{ $current === $value ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>

        <form method="GET" class="row" style="gap:8px;">
            @if($current)<input type="hidden" name="status" value="{{ $current }}">@endif
            <select name="type" class="select" style="width:150px;" onchange="this.form.submit()">
                <option value="">All types</option>
                @foreach(['technical' => 'Technical', 'financial' => 'Financial', 'combined' => 'Combined'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <x-ui.card :flush="true">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Report</th><th>Award</th><th>Period</th><th>Due</th>
                        <th>Status</th><th>Sent to donor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        @php
                            $days = Days::until($report->due_date);
                            $late = $report->status === 'pending' && $days < 0;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('reports.show', $report) }}" class="strong">{{ Str::limit($report->title, 40) }}</a>
                                <div class="tiny muted-3">{{ ucfirst($report->report_type) }}@if($report->submittedBy) · {{ $report->submittedBy->name }}@endif</div>
                            </td>
                            <td>
                                <div class="small">{{ Str::limit($report->award?->title, 32) }}</div>
                                <div class="tiny muted-3 mono">{{ $report->award?->contract_number }}</div>
                            </td>
                            <td class="small muted">
                                {{ $report->period_start?->format('M Y') }} – {{ $report->period_end?->format('M Y') }}
                            </td>
                            <td class="small">
                                {{ $report->due_date?->format('d M Y') }}
                                @if($late)
                                    <div class="tiny text-danger">{{ abs($days) }}d overdue</div>
                                @elseif($report->status === 'pending' && $days <= 14)
                                    <div class="tiny text-warn">in {{ $days }}d</div>
                                @endif
                            </td>
                            <td>
                                <x-ui.pill :tone="match(true) {
                                    $report->status === 'approved' => 'ok',
                                    $report->status === 'rejected' => 'danger',
                                    $late => 'danger',
                                    $report->status === 'under_review' => 'warn',
                                    default => null,
                                }">{{ Str::headline($report->status) }}</x-ui.pill>
                            </td>
                            <td class="small">
                                @if($report->sent_to_donor_on)
                                    <span class="text-ok">{{ $report->sent_to_donor_on->format('d M Y') }}</span>
                                @elseif($report->status === 'approved')
                                    <span class="text-warn">approved, not sent</span>
                                @else
                                    <span class="muted-3">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <x-ui.empty icon="fa-file-invoice" title="No reports">
                                Reporting deadlines are generated when an award is set up, from the frequency agreed
                                with the donor.
                            </x-ui.empty>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div class="pagination-wrap">{{ $reports->links() }}</div>
</div>
@endsection
