@extends('layouts.gmu')

@section('title', 'Edit award')
@section('crumb', 'Awards & compliance')

@section('content')
<x-ui.page-head title="Edit award" :lede="$award->contract_number . ' · ' . $award->title">
    <x-slot:actions>
        <a href="{{ route('awards.show', $award) }}" class="btn">View award</a>
    </x-slot:actions>
</x-ui.page-head>

<form method="POST" action="{{ route('awards.update', $award) }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="grid grid-wide">
        <div class="stack">
            <x-ui.card title="The contract">
                <div class="form-grid">
                    <x-ui.field label="Award title" name="title" required wide>
                        <input name="title" class="input" value="{{ old('title', $award->title) }}" required maxlength="255">
                    </x-ui.field>

                    <x-ui.field label="Contract number" name="contract_number" required>
                        <input name="contract_number" class="input mono" value="{{ old('contract_number', $award->contract_number) }}" required>
                    </x-ui.field>

                    <x-ui.field label="Total budget" name="total_budget" required
                                help="For a donor-approved change, record an amendment instead — it keeps the history">
                        <input type="number" step="0.01" min="0" name="total_budget" class="input"
                               value="{{ old('total_budget', $award->total_budget) }}" required>
                    </x-ui.field>

                    <x-ui.field label="Currency" name="currency" required>
                        <select name="currency" class="select" required>
                            @foreach(\App\Support\Money::supported() as $code)
                                <option value="{{ $code }}" @selected(old('currency', $award->currency) === $code)>{{ $code }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Notes" name="notes" wide>
                        <textarea name="notes" rows="3" class="textarea">{{ old('notes', $award->notes) }}</textarea>
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card title="Period and reporting">
                <div class="form-grid">
                    <x-ui.field label="Start date" name="start_date" required>
                        <input type="date" name="start_date" class="input" value="{{ old('start_date', $award->start_date?->toDateString()) }}" required>
                    </x-ui.field>

                    <x-ui.field label="End date" name="end_date" required>
                        <input type="date" name="end_date" class="input" value="{{ old('end_date', $award->end_date?->toDateString()) }}" required>
                    </x-ui.field>

                    <x-ui.field label="Kickoff briefing" name="kickoff_date">
                        <input type="date" name="kickoff_date" class="input" value="{{ old('kickoff_date', $award->kickoff_date?->toDateString()) }}">
                    </x-ui.field>

                    <x-ui.field label="Reporting frequency" name="reporting_frequency" required>
                        <select name="reporting_frequency" class="select" required>
                            @foreach(\App\Services\ComplianceScheduleGenerator::frequencies() as $value => $label)
                                <option value="{{ $value }}" @selected(old('reporting_frequency', $award->reporting_frequency) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                </div>
            </x-ui.card>
        </div>

        <div class="stack">
            <x-ui.card title="Status and contract file">
                <div class="stack-sm">
                    <x-ui.field label="Status" name="status" required>
                        <select name="status" class="select" required>
                            @foreach(['active' => 'Active', 'suspended' => 'Suspended', 'completed' => 'Completed', 'terminated' => 'Terminated'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $award->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Replace signed contract" name="signed_contract">
                        @if($award->signed_contract)
                            <a href="{{ route('awards.contract', $award) }}" class="btn btn-sm" style="margin-bottom:8px;">
                                <i class="fas fa-file-contract"></i> Current contract
                            </a>
                        @endif
                        <input type="file" name="signed_contract" class="input" accept=".pdf,.doc,.docx">
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="form-actions">
                    <button class="btn btn-primary">Save changes</button>
                    <a href="{{ route('awards.show', $award) }}" class="btn">Cancel</a>
                </div>
            </x-ui.card>

            @can('delete', $award)
                <x-ui.card title="Remove this award" accent="danger">
                    <p class="small muted" style="line-height:1.7;">
                        Soft-deletes the award. Reports, budgets and compliance records stay in the database.
                        Prefer marking it terminated, which keeps it visible.
                    </p>
                    <form method="POST" action="{{ route('awards.destroy', $award) }}"
                          onsubmit="return confirm('Remove this award?')" style="margin-top:12px;">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-block">Remove award</button>
                    </form>
                </x-ui.card>
            @endcan
        </div>
    </div>
</form>
@endsection
