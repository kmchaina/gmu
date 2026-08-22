@extends('adminlte::page')

@section('title', 'Register Proposal')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-plus-circle mr-2"></i>Register New Proposal</h1>
        <a href="{{ route('proposals.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
@endsection

@section('content')

<form action="{{ route('proposals.store') }}" method="POST">
@csrf

<div class="row">
    <div class="col-md-8">

        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Proposal Details</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label>Proposal Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}"
                           class="form-control @error('title') is-invalid @enderror"
                           placeholder="Full title of the research proposal">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Linked Funding Call <span class="text-danger">*</span></label>
                    <select name="funding_call_id" class="form-control select2 @error('funding_call_id') is-invalid @enderror">
                        <option value="">-- Select Funding Call --</option>
                        @foreach($calls as $call)
                            <option value="{{ $call->id }}"
                                {{ old('funding_call_id', $selectedCall?->id) == $call->id ? 'selected' : '' }}>
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
                                    <option value="{{ $pi->id }}" {{ old('lead_pi_id') == $pi->id ? 'selected' : '' }}>
                                        {{ $pi->name }} ({{ $pi->role?->display_name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('lead_pi_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Initiation Type <span class="text-danger">*</span></label>
                            <select name="initiation_type" class="form-control">
                                <option value="gmu_initiated"       {{ old('initiation_type','gmu_initiated') === 'gmu_initiated'       ? 'selected' : '' }}>GMU Initiated</option>
                                <option value="researcher_initiated" {{ old('initiation_type') === 'researcher_initiated' ? 'selected' : '' }}>Researcher Initiated</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Abstract / Summary</label>
                    <textarea name="abstract" rows="5" class="form-control"
                              placeholder="Brief description of the research objectives, methodology, and expected outcomes...">{{ old('abstract') }}</textarea>
                </div>

            </div>
        </div>

        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title">Budget & Timeline</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Requested Budget</label>
                            <input type="number" name="requested_budget" value="{{ old('requested_budget') }}"
                                   class="form-control" placeholder="0.00" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Currency</label>
                            <select name="currency" class="form-control">
                                <option value="USD" {{ old('currency','USD') === 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>GBP</option>
                                <option value="TZS" {{ old('currency') === 'TZS' ? 'selected' : '' }}>TZS</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Target Submission Date</label>
                            <input type="date" name="target_submission_date" value="{{ old('target_submission_date') }}" class="form-control">
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
                <div class="form-group">
                    <label>Current Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-control @error('status') is-invalid @enderror">
                        <option value="concept"           {{ old('status','concept') === 'concept'           ? 'selected' : '' }}>Concept Stage</option>
                        <option value="under_development" {{ old('status') === 'under_development' ? 'selected' : '' }}>Under Development</option>
                        <option value="internal_review"   {{ old('status') === 'internal_review'   ? 'selected' : '' }}>Internal Review</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <small class="text-muted">
                    Status will automatically advance to <strong>Approved</strong> once all three internal reviews are completed.
                </small>
            </div>
        </div>

        <div class="card bg-light">
            <div class="card-body">
                <p class="font-weight-bold mb-2 small"><i class="fas fa-info-circle mr-1 text-primary"></i>Review Process</p>
                <ul class="small text-muted pl-3 mb-0">
                    <li><strong>Technical Review</strong> — Research Scientist</li>
                    <li><strong>Compliance Review</strong> — Legal Officer</li>
                    <li><strong>Budget Review</strong> — Finance Officer</li>
                </ul>
                <p class="small text-muted mt-2 mb-0">Three review slots will be created automatically when the proposal is registered.</p>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save mr-1"></i> Register Proposal
        </button>
        <a href="{{ route('proposals.index') }}" class="btn btn-default btn-block">Cancel</a>

    </div>
</div>

</form>

@endsection
