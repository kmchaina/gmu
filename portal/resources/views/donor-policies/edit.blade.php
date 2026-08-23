@extends('layouts.gmu')

@section('title', 'Edit donor guidance')
@section('crumb', 'Library')

@section('content')
<x-ui.page-head title="Edit donor guidance" :lede="$donorPolicy->title">
    <x-slot:actions>
        <a href="{{ route('donor-policies.show', $donorPolicy) }}" class="btn"><i class="fas fa-download"></i> Open current file</a>
    </x-slot:actions>
</x-ui.page-head>

<form method="POST" action="{{ route('donor-policies.update', $donorPolicy) }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="grid grid-wide">
        <x-ui.card title="The document">
            <div class="form-grid">
                <x-ui.field label="Donor" name="donor_id" required>
                    <select name="donor_id" class="select" required>
                        @foreach($donors as $donor)
                            <option value="{{ $donor->id }}" @selected(old('donor_id', $donorPolicy->donor_id) == $donor->id)>{{ $donor->name }}</option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field label="Type" name="policy_type" required>
                    <select name="policy_type" class="select" required>
                        @foreach(['grant_guidelines' => 'Grant guidelines', 'reporting_template' => 'Reporting template', 'budget_template' => 'Budget template', 'compliance_policy' => 'Compliance policy', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('policy_type', $donorPolicy->policy_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field label="Title" name="title" required wide>
                    <input name="title" class="input" value="{{ old('title', $donorPolicy->title) }}" required maxlength="255">
                </x-ui.field>

                <x-ui.field label="Version" name="version" required>
                    <input type="number" name="version" min="1" class="input" value="{{ old('version', $donorPolicy->version) }}" required>
                </x-ui.field>

                <x-ui.field label="Effective from" name="effective_date">
                    <input type="date" name="effective_date" class="input"
                           value="{{ old('effective_date', $donorPolicy->effective_date?->toDateString()) }}">
                </x-ui.field>

                <x-ui.field label="Notes" name="notes" wide>
                    <textarea name="notes" rows="3" class="textarea">{{ old('notes', $donorPolicy->notes) }}</textarea>
                </x-ui.field>

                <x-ui.field label="Replace file" name="file" wide
                            help="Leave empty to keep {{ $donorPolicy->file_name }}">
                    <input type="file" name="file" class="input" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv">
                </x-ui.field>
            </div>
        </x-ui.card>

        <div class="stack">
            <x-ui.card>
                <div class="form-actions">
                    <button class="btn btn-primary">Save changes</button>
                    <a href="{{ route('donor-policies.index') }}" class="btn">Cancel</a>
                </div>
            </x-ui.card>

            @can('delete', $donorPolicy)
                <x-ui.card title="Remove this document" accent="danger">
                    <form method="POST" action="{{ route('donor-policies.destroy', $donorPolicy) }}"
                          onsubmit="return confirm('Remove this document and its file?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-block">Remove document</button>
                    </form>
                </x-ui.card>
            @endcan
        </div>
    </div>
</form>
@endsection
