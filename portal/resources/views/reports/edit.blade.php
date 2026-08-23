@extends('layouts.gmu')

@section('title', 'Update report')
@section('crumb', 'Awards & compliance')

@section('content')
@php $canVerify = auth()->user()->can('verify', $report); @endphp

<x-ui.page-head title="Update report" :lede="$report->title . ' · ' . $report->award?->contract_number">
    <x-slot:actions>
        <a href="{{ route('reports.show', $report) }}" class="btn">View report</a>
    </x-slot:actions>
</x-ui.page-head>

<form method="POST" action="{{ route('reports.update', $report) }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="grid grid-wide">
        <div class="stack">
            <x-ui.card title="The report">
                <div class="form-grid">
                    <x-ui.field label="Title" name="title" required wide>
                        <input name="title" class="input" value="{{ old('title', $report->title) }}" required maxlength="255">
                    </x-ui.field>

                    <x-ui.field label="Type" name="report_type" required>
                        <select name="report_type" class="select" required>
                            @foreach(['technical' => 'Technical', 'financial' => 'Financial', 'combined' => 'Combined'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('report_type', $report->report_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Due date" name="due_date" required>
                        <input type="date" name="due_date" class="input" value="{{ old('due_date', $report->due_date?->toDateString()) }}" required>
                    </x-ui.field>

                    <x-ui.field label="Period start" name="period_start">
                        <input type="date" name="period_start" class="input" value="{{ old('period_start', $report->period_start?->toDateString()) }}">
                    </x-ui.field>

                    <x-ui.field label="Period end" name="period_end">
                        <input type="date" name="period_end" class="input" value="{{ old('period_end', $report->period_end?->toDateString()) }}">
                    </x-ui.field>

                    <x-ui.field label="Replace file" name="file_path" wide>
                        @if($report->file_path)
                            <a href="{{ route('reports.download', $report) }}" class="btn btn-sm" style="margin-bottom:8px;">
                                <i class="fas fa-file"></i> Current file
                            </a>
                        @endif
                        <input type="file" name="file_path" class="input" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv">
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card title="GMU review">
                <x-ui.field label="Comments" name="gmu_comments" help="If you are returning the report, say what needs to change">
                    <textarea name="gmu_comments" rows="4" class="textarea">{{ old('gmu_comments', $report->gmu_comments) }}</textarea>
                </x-ui.field>
            </x-ui.card>
        </div>

        <div class="stack">
            <x-ui.card title="Status">
                <x-ui.field label="Status" name="status" required>
                    <select name="status" class="select" required>
                        @foreach(['pending' => 'Pending', 'submitted' => 'Submitted', 'under_review' => 'Under review', 'rejected' => 'Returned to the team'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $report->status) === $value)>{{ $label }}</option>
                        @endforeach
                        @if($canVerify || $report->status === 'approved')
                            <option value="approved" @selected(old('status', $report->status) === 'approved')>Approved — cleared for the donor</option>
                        @endif
                    </select>
                </x-ui.field>

                @unless($canVerify)
                    <p class="small muted" style="margin-top:10px; line-height:1.65;">
                        Approving a report is a second pair of eyes: it is done by the Coordinator or Finance Officer,
                        and never by whoever submitted it.
                    </p>
                @endunless
            </x-ui.card>

            <x-ui.card title="Submission to the donor" note="Separate from internal approval">
                <div class="stack-sm">
                    <x-ui.field label="Sent to donor on" name="sent_to_donor_on"
                                help="The date it actually went out">
                        <input type="date" name="sent_to_donor_on" class="input"
                               value="{{ old('sent_to_donor_on', $report->sent_to_donor_on?->toDateString()) }}">
                    </x-ui.field>

                    <x-ui.field label="Donor acknowledgement" name="donor_acknowledgement">
                        <input name="donor_acknowledgement" class="input"
                               value="{{ old('donor_acknowledgement', $report->donor_acknowledgement) }}"
                               placeholder="Reference or confirmation">
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="form-actions">
                    <button class="btn btn-primary">Save changes</button>
                    <a href="{{ route('reports.show', $report) }}" class="btn">Cancel</a>
                </div>
            </x-ui.card>

            @can('delete', $report)
                <x-ui.card title="Remove this report" accent="danger">
                    <form method="POST" action="{{ route('reports.destroy', $report) }}"
                          onsubmit="return confirm('Remove this report?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-block">Remove report</button>
                    </form>
                </x-ui.card>
            @endcan
        </div>
    </div>
</form>
@endsection
