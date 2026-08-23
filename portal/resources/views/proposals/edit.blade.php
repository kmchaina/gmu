@extends('layouts.gmu')

@section('title', 'Edit proposal')
@section('crumb', 'Pipeline')

@section('content')
<x-ui.page-head title="Edit proposal" :lede="$proposal->title">
    <x-slot:actions>
        <a href="{{ route('proposals.show', $proposal) }}" class="btn">View proposal</a>
    </x-slot:actions>
</x-ui.page-head>

<form method="POST" action="{{ route('proposals.update', $proposal) }}">
    @csrf @method('PUT')

    <div class="grid grid-wide">
        <div class="stack">
            <x-ui.card title="The proposal">
                <div class="form-grid">
                    <x-ui.field label="Funding call" name="funding_call_id" required wide>
                        <select name="funding_call_id" class="select" required>
                            @foreach($calls as $call)
                                <option value="{{ $call->id }}" @selected(old('funding_call_id', $proposal->funding_call_id) == $call->id)>
                                    {{ $call->title }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Title" name="title" required wide>
                        <input name="title" class="input" value="{{ old('title', $proposal->title) }}" required maxlength="255">
                    </x-ui.field>

                    <x-ui.field label="Abstract" name="abstract" wide>
                        <textarea name="abstract" rows="4" class="textarea">{{ old('abstract', $proposal->abstract) }}</textarea>
                    </x-ui.field>

                    <x-ui.field label="Lead PI" name="lead_pi_id" required>
                        <select name="lead_pi_id" class="select" required>
                            @foreach($pis as $pi)
                                <option value="{{ $pi->id }}" @selected(old('lead_pi_id', $proposal->lead_pi_id) == $pi->id)>{{ $pi->name }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Assigned officer" name="assigned_officer_id">
                        <select name="assigned_officer_id" class="select">
                            <option value="">Unassigned</option>
                            @foreach($officers as $officer)
                                <option value="{{ $officer->id }}" @selected(old('assigned_officer_id', $proposal->assigned_officer_id) == $officer->id)>{{ $officer->name }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Requested budget" name="requested_budget">
                        <input type="number" step="0.01" min="0" name="requested_budget" class="input"
                               value="{{ old('requested_budget', $proposal->requested_budget) }}">
                    </x-ui.field>

                    <x-ui.field label="Currency" name="currency" required>
                        <select name="currency" class="select" required>
                            @foreach(\App\Support\Money::supported() as $code)
                                <option value="{{ $code }}" @selected(old('currency', $proposal->currency) === $code)>{{ $code }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card title="Dates and outcome">
                <div class="form-grid">
                    <x-ui.field label="Target submission" name="target_submission_date">
                        <input type="date" name="target_submission_date" class="input"
                               value="{{ old('target_submission_date', $proposal->target_submission_date?->toDateString()) }}">
                    </x-ui.field>

                    <x-ui.field label="Submitted to donor" name="submitted_at">
                        <input type="date" name="submitted_at" class="input"
                               value="{{ old('submitted_at', $proposal->submitted_at?->toDateString()) }}">
                    </x-ui.field>

                    <x-ui.field label="Outcome date" name="outcome_date">
                        <input type="date" name="outcome_date" class="input"
                               value="{{ old('outcome_date', $proposal->outcome_date?->toDateString()) }}">
                    </x-ui.field>

                    <x-ui.field label="Outcome notes" name="outcome_notes" wide help="What the donor said, in their words where possible">
                        <textarea name="outcome_notes" rows="3" class="textarea">{{ old('outcome_notes', $proposal->outcome_notes) }}</textarea>
                    </x-ui.field>
                </div>
            </x-ui.card>
        </div>

        <div class="stack">
            <x-ui.card title="Stage">
                <div class="stack-sm">
                    <x-ui.field label="Status" name="status" required
                                help="Moving to internal review notifies the officers who hold each track">
                        <select name="status" class="select" required>
                            @foreach(['concept','under_development','internal_review','approved','submitted','awarded','declined','withdrawn'] as $value)
                                <option value="{{ $value }}" @selected(old('status', $proposal->status) === $value)>{{ Str::headline($value) }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Origin" name="initiation_type" required>
                        <select name="initiation_type" class="select" required>
                            <option value="gmu_initiated" @selected(old('initiation_type', $proposal->initiation_type) === 'gmu_initiated')>GMU initiated</option>
                            <option value="researcher_initiated" @selected(old('initiation_type', $proposal->initiation_type) === 'researcher_initiated')>Researcher initiated</option>
                        </select>
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="form-actions">
                    <button class="btn btn-primary">Save changes</button>
                    <a href="{{ route('proposals.show', $proposal) }}" class="btn">Cancel</a>
                </div>
            </x-ui.card>

            @can('delete', $proposal)
                <x-ui.card title="Remove this proposal" accent="danger">
                    <p class="small muted" style="line-height:1.7;">
                        The proposal is soft-deleted: its reviews and documents stay in the database and it can be
                        restored by an administrator.
                    </p>
                    <form method="POST" action="{{ route('proposals.destroy', $proposal) }}"
                          onsubmit="return confirm('Remove this proposal?')" style="margin-top:12px;">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-block">Remove proposal</button>
                    </form>
                </x-ui.card>
            @endcan
        </div>
    </div>
</form>
@endsection
