@extends('adminlte::page')

@section('title', 'Edit Proposal')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-edit mr-2"></i>Edit Proposal</h1>
        <a href="{{ route('proposals.show', $proposal) }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
@endsection

@section('content')

<form action="{{ route('proposals.update', $proposal) }}" method="POST">
@csrf
@method('PUT')

<div class="row">
    <div class="col-md-8">

        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Proposal Details</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label>Proposal Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $proposal->title) }}"
                           class="form-control @error('title') is-invalid @enderror">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Linked Funding Call <span class="text-danger">*</span></label>
                    <select name="funding_call_id" class="form-control select2 @error('funding_call_id') is-invalid @enderror">
                        <option value="">-- Select Funding Call --</option>
                        @foreach($calls as $call)
                            <option value="{{ $call->id }}" {{ old('funding_call_id', $proposal->funding_call_id) == $call->id ? 'selected' : '' }}>
                                {{ $call->title }} — {{ $call->donor->acronym ?? $call->donor->name }}
                                (deadline: {{ $call->deadline->format('d M Y') }})
                            </option>
                        @endforeach
                    </select>
                    @error('funding_call_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Lead Principal Investigator <span class="text-danger">*</span></label>
                            <select name="lead_pi_id" class="form-control select2 @error('lead_pi_id') is-invalid @enderror">
                                <option value="">-- Select Lead PI --</option>
                                @foreach($pis as $pi)
                                    <option value="{{ $pi->id }}" {{ old('lead_pi_id', $proposal->lead_pi_id) == $pi->id ? 'selected' : '' }}>
                                        {{ $pi->name }} ({{ $pi->role?->display_name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('lead_pi_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>GMU Officer</label>
                            <select name="assigned_officer_id" class="form-control select2">
                                <option value="">-- Unassigned --</option>
                                @foreach($officers as $officer)
                                    <option value="{{ $officer->id }}" {{ old('assigned_officer_id', $proposal->assigned_officer_id) == $officer->id ? 'selected' : '' }}>
                                        {{ $officer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Initiation Type</label>
                            <select name="initiation_type" class="form-control">
                                <option value="gmu_initiated"       {{ old('initiation_type', $proposal->initiation_type) === 'gmu_initiated'       ? 'selected' : '' }}>GMU Initiated</option>
                                <option value="researcher_initiated" {{ old('initiation_type', $proposal->initiation_type) === 'researcher_initiated' ? 'selected' : '' }}>Researcher Initiated</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Abstract / Summary</label>
                    <textarea name="abstract" rows="5" class="form-control">{{ old('abstract', $proposal->abstract) }}</textarea>
                </div>

            </div>
        </div>

        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title">Budget & Timeline</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Requested Budget</label>
                            <input type="number" name="requested_budget" value="{{ old('requested_budget', $proposal->requested_budget) }}"
                                   class="form-control" placeholder="0.00" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Currency</label>
                            <select name="currency" class="form-control">
                                <option value="USD" {{ old('currency', $proposal->currency) === 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="EUR" {{ old('currency', $proposal->currency) === 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="GBP" {{ old('currency', $proposal->currency) === 'GBP' ? 'selected' : '' }}>GBP</option>
                                <option value="TZS" {{ old('currency', $proposal->currency) === 'TZS' ? 'selected' : '' }}>TZS</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Target Submission</label>
                            <input type="date" name="target_submission_date"
                                   value="{{ old('target_submission_date', $proposal->target_submission_date?->format('Y-m-d')) }}"
                                   class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Actual Submitted Date</label>
                            <input type="date" name="submitted_at"
                                   value="{{ old('submitted_at', $proposal->submitted_at?->format('Y-m-d')) }}"
                                   class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-danger">
            <div class="card-header"><h3 class="card-title">Outcome (if known)</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Outcome Date</label>
                            <input type="date" name="outcome_date"
                                   value="{{ old('outcome_date', $proposal->outcome_date?->format('Y-m-d')) }}"
                                   class="form-control">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Outcome Notes</label>
                            <textarea name="outcome_notes" rows="2" class="form-control"
                                      placeholder="Details about award or decline decision...">{{ old('outcome_notes', $proposal->outcome_notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="col-md-4">

        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Status</h3></div>
            <div class="card-body">
                <div class="form-group mb-0">
                    <label>Current Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-control @error('status') is-invalid @enderror">
                        <option value="concept"           {{ old('status', $proposal->status) === 'concept'           ? 'selected' : '' }}>Concept Stage</option>
                        <option value="under_development" {{ old('status', $proposal->status) === 'under_development' ? 'selected' : '' }}>Under Development</option>
                        <option value="internal_review"   {{ old('status', $proposal->status) === 'internal_review'   ? 'selected' : '' }}>Internal Review</option>
                        <option value="approved"          {{ old('status', $proposal->status) === 'approved'          ? 'selected' : '' }}>Approved</option>
                        <option value="submitted"         {{ old('status', $proposal->status) === 'submitted'         ? 'selected' : '' }}>Submitted to Donor</option>
                        <option value="awarded"           {{ old('status', $proposal->status) === 'awarded'           ? 'selected' : '' }}>Awarded</option>
                        <option value="declined"          {{ old('status', $proposal->status) === 'declined'          ? 'selected' : '' }}>Declined</option>
                        <option value="withdrawn"         {{ old('status', $proposal->status) === 'withdrawn'         ? 'selected' : '' }}>Withdrawn</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save mr-1"></i> Save Changes
        </button>
        <a href="{{ route('proposals.show', $proposal) }}" class="btn btn-default btn-block">Cancel</a>

    </div>
</div>

</form>

@endsection
