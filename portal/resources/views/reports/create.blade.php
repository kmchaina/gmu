@extends('layouts.gmu')

@section('title', 'Record a report')
@section('crumb', 'Awards & compliance')

@section('content')
<x-ui.page-head
    title="Record a report"
    lede="Most reporting deadlines are generated when an award is set up. Use this for anything outside that schedule." />

<form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="grid grid-wide">
        <div class="stack">
            <x-ui.card title="The report">
                <div class="form-grid">
                    <x-ui.field label="Award" name="award_id" required wide>
                        <select name="award_id" class="select" required>
                            <option value="">Choose the award…</option>
                            @foreach($awards as $award)
                                <option value="{{ $award->id }}" @selected(old('award_id', $selectedAward?->id) == $award->id)>
                                    {{ $award->contract_number }} — {{ Str::limit($award->title, 44) }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Title" name="title" required wide>
                        <input name="title" class="input" value="{{ old('title') }}" required maxlength="255"
                               placeholder="Technical report — period 3">
                    </x-ui.field>

                    <x-ui.field label="Type" name="report_type" required>
                        <select name="report_type" class="select" required>
                            @foreach(['technical' => 'Technical', 'financial' => 'Financial', 'combined' => 'Combined'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('report_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Due date" name="due_date" required>
                        <input type="date" name="due_date" class="input" value="{{ old('due_date') }}" required>
                    </x-ui.field>

                    <x-ui.field label="Period start" name="period_start">
                        <input type="date" name="period_start" class="input" value="{{ old('period_start') }}">
                    </x-ui.field>

                    <x-ui.field label="Period end" name="period_end">
                        <input type="date" name="period_end" class="input" value="{{ old('period_end') }}">
                    </x-ui.field>
                </div>
            </x-ui.card>
        </div>

        <div class="stack">
            <x-ui.card title="Status and file">
                <div class="stack-sm">
                    <x-ui.field label="Status" name="status" required>
                        <select name="status" class="select" required>
                            @foreach(['pending' => 'Pending — not yet submitted', 'submitted' => 'Submitted', 'under_review' => 'Under GMU review'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'pending') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Report file" name="file_path" help="PDF, Word or Excel, up to 20 MB">
                        <input type="file" name="file_path" class="input" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv">
                    </x-ui.field>

                    <x-ui.field label="GMU comments" name="gmu_comments">
                        <textarea name="gmu_comments" rows="3" class="textarea">{{ old('gmu_comments') }}</textarea>
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="form-actions">
                    <button class="btn btn-primary">Record report</button>
                    <a href="{{ route('reports.index') }}" class="btn">Cancel</a>
                </div>
            </x-ui.card>
        </div>
    </div>
</form>
@endsection
