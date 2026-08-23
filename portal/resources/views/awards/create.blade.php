@extends('layouts.gmu')

@section('title', 'Record an award')
@section('crumb', 'Awards & compliance')

@section('content')
<x-ui.page-head
    title="Record an award"
    lede="Recording the award generates the reporting calendar from the frequency agreed with the donor, and tells the team." />

<form method="POST" action="{{ route('awards.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="grid grid-wide">
        <div class="stack">
            <x-ui.card title="The contract">
                <div class="form-grid">
                    <x-ui.field label="Funded proposal" name="proposal_id" required wide>
                        <select name="proposal_id" class="select" required>
                            <option value="">Choose the proposal…</option>
                            @foreach($proposals as $proposal)
                                <option value="{{ $proposal->id }}" @selected(old('proposal_id', $selectedProposal?->id) == $proposal->id)>
                                    {{ $proposal->title }} — {{ $proposal->leadPi?->name }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Award title" name="title" required wide>
                        <input name="title" class="input" value="{{ old('title', $selectedProposal?->title) }}" required maxlength="255">
                    </x-ui.field>

                    <x-ui.field label="Contract number" name="contract_number" required help="As it appears on the donor agreement">
                        <input name="contract_number" class="input mono" value="{{ old('contract_number') }}" required maxlength="100">
                    </x-ui.field>

                    <x-ui.field label="Donor" name="donor_id" required>
                        <select name="donor_id" class="select" required>
                            @foreach($donors as $donor)
                                <option value="{{ $donor->id }}"
                                        @selected(old('donor_id', $selectedProposal?->fundingCall?->donor_id) == $donor->id)>{{ $donor->name }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Total budget" name="total_budget" required>
                        <input type="number" step="0.01" min="0" name="total_budget" class="input"
                               value="{{ old('total_budget', $selectedProposal?->requested_budget) }}" required>
                    </x-ui.field>

                    <x-ui.field label="Currency" name="currency" required help="Portfolio figures convert at the rate booked today">
                        <select name="currency" class="select" required>
                            @foreach(\App\Support\Money::supported() as $code)
                                <option value="{{ $code }}"
                                        @selected(old('currency', $selectedProposal?->currency ?? \App\Support\Money::base()) === $code)>{{ $code }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card title="Period and reporting">
                <div class="form-grid">
                    <x-ui.field label="Start date" name="start_date" required>
                        <input type="date" name="start_date" class="input" value="{{ old('start_date') }}" required>
                    </x-ui.field>

                    <x-ui.field label="End date" name="end_date" required>
                        <input type="date" name="end_date" class="input" value="{{ old('end_date') }}" required>
                    </x-ui.field>

                    <x-ui.field label="Kickoff briefing" name="kickoff_date">
                        <input type="date" name="kickoff_date" class="input" value="{{ old('kickoff_date') }}">
                    </x-ui.field>

                    <x-ui.field label="Reporting frequency" name="reporting_frequency" required
                                help="A technical and a financial report per period, each due 30 days after it closes, plus kickoff, mid-term and closeout milestones">
                        <select name="reporting_frequency" class="select" required>
                            @foreach(\App\Services\ComplianceScheduleGenerator::frequencies() as $value => $label)
                                <option value="{{ $value }}" @selected(old('reporting_frequency', 'quarterly') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Notes" name="notes" wide>
                        <textarea name="notes" rows="2" class="textarea">{{ old('notes') }}</textarea>
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
                                <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Signed contract" name="signed_contract" help="An auditor will ask for this">
                        <input type="file" name="signed_contract" class="input" accept=".pdf,.doc,.docx">
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="form-actions">
                    <button class="btn btn-primary"><i class="fas fa-trophy"></i> Record the award</button>
                    <a href="{{ route('awards.index') }}" class="btn">Cancel</a>
                </div>
            </x-ui.card>
        </div>
    </div>
</form>
@endsection
