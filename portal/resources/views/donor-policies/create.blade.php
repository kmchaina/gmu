@extends('layouts.gmu')

@section('title', 'Upload donor guidance')
@section('crumb', 'Library')

@section('content')
<x-ui.page-head
    title="Upload donor guidance"
    lede="Guidelines, reporting and budget templates — what a PI needs before writing, and the GMU needs before reviewing." />

<form method="POST" action="{{ route('donor-policies.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="grid grid-wide">
        <x-ui.card title="The document">
            <div class="form-grid">
                <x-ui.field label="Donor" name="donor_id" required>
                    <select name="donor_id" class="select" required>
                        <option value="">Choose a donor…</option>
                        @foreach($donors as $donor)
                            <option value="{{ $donor->id }}" @selected(old('donor_id', $selectedDonor?->id) == $donor->id)>{{ $donor->name }}</option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field label="Type" name="policy_type" required>
                    <select name="policy_type" class="select" required>
                        @foreach(['grant_guidelines' => 'Grant guidelines', 'reporting_template' => 'Reporting template', 'budget_template' => 'Budget template', 'compliance_policy' => 'Compliance policy', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('policy_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field label="Title" name="title" required wide>
                    <input name="title" class="input" value="{{ old('title') }}" required maxlength="255">
                </x-ui.field>

                <x-ui.field label="Version" name="version" required>
                    <input type="number" name="version" min="1" class="input" value="{{ old('version', 1) }}" required>
                </x-ui.field>

                <x-ui.field label="Effective from" name="effective_date">
                    <input type="date" name="effective_date" class="input" value="{{ old('effective_date') }}">
                </x-ui.field>

                <x-ui.field label="Notes" name="notes" wide help="What changed in this version, or what to watch for">
                    <textarea name="notes" rows="3" class="textarea">{{ old('notes') }}</textarea>
                </x-ui.field>

                <x-ui.field label="File" name="file" required wide help="PDF, Word or Excel, up to 20 MB">
                    <input type="file" name="file" class="input" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv" required>
                </x-ui.field>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="form-actions">
                <button class="btn btn-primary"><i class="fas fa-upload"></i> Upload</button>
                <a href="{{ route('donor-policies.index') }}" class="btn">Cancel</a>
            </div>
        </x-ui.card>
    </div>
</form>
@endsection
