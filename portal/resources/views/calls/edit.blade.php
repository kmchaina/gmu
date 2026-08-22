@extends('adminlte::page')

@section('title', 'Edit Funding Call')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-edit mr-2"></i>Edit Funding Call</h1>
        <a href="{{ route('calls.show', $call) }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
@endsection

@section('content')

<form action="{{ route('calls.update', $call) }}" method="POST" enctype="multipart/form-data">
@csrf
@method('PUT')

<div class="row">
    <div class="col-md-8">

        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Call Details</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label>Call Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $call->title) }}"
                           class="form-control @error('title') is-invalid @enderror"
                           placeholder="e.g. Call for Research Proposals on Malaria Prevention 2026">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Donor / Funder <span class="text-danger">*</span></label>
                            <select name="donor_id" class="form-control select2 @error('donor_id') is-invalid @enderror">
                                <option value="">-- Select Donor --</option>
                                @foreach($donors as $donor)
                                    <option value="{{ $donor->id }}" {{ old('donor_id', $call->donor_id) == $donor->id ? 'selected' : '' }}>
                                        {{ $donor->name }} @if($donor->acronym)({{ $donor->acronym }})@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('donor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small><a href="{{ route('donors.create') }}" target="_blank">+ Add new donor</a></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Solicitation / Reference Number</label>
                            <input type="text" name="solicitation_number" value="{{ old('solicitation_number', $call->solicitation_number) }}"
                                   class="form-control" placeholder="e.g. RFA-TW-26-001">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Research Theme / Area</label>
                    <input type="text" name="theme" value="{{ old('theme', $call->theme) }}"
                           class="form-control" placeholder="e.g. Infectious Diseases, Maternal Health, Nutrition...">
                </div>

                <div class="form-group">
                    <label>Description / Summary</label>
                    <textarea name="description" rows="4" class="form-control"
                              placeholder="Describe the funding opportunity, objectives, and scope...">{{ old('description', $call->description) }}</textarea>
                </div>

                <div class="form-group">
                    <label>Eligibility Requirements</label>
                    <textarea name="eligibility" rows="3" class="form-control"
                              placeholder="Who can apply? Institutions, qualifications, countries...">{{ old('eligibility', $call->eligibility) }}</textarea>
                </div>

            </div>
        </div>

        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title">Budget & Dates</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Estimated Budget</label>
                            <input type="number" name="estimated_budget" value="{{ old('estimated_budget', $call->estimated_budget) }}"
                                   class="form-control" placeholder="0.00" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Currency</label>
                            <select name="currency" class="form-control">
                                <option value="USD" {{ old('currency', $call->currency) === 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="EUR" {{ old('currency', $call->currency) === 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="GBP" {{ old('currency', $call->currency) === 'GBP' ? 'selected' : '' }}>GBP</option>
                                <option value="TZS" {{ old('currency', $call->currency) === 'TZS' ? 'selected' : '' }}>TZS</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Open Date</label>
                            <input type="date" name="open_date"
                                   value="{{ old('open_date', $call->open_date?->format('Y-m-d')) }}"
                                   class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Submission Deadline <span class="text-danger">*</span></label>
                            <input type="date" name="deadline"
                                   value="{{ old('deadline', $call->deadline->format('Y-m-d')) }}"
                                   class="form-control @error('deadline') is-invalid @enderror">
                            @error('deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="col-md-4">

        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Settings</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label>Initiation Type <span class="text-danger">*</span></label>
                    <select name="initiation_type" class="form-control">
                        <option value="gmu_posted"           {{ old('initiation_type', $call->initiation_type) === 'gmu_posted' ? 'selected' : '' }}>GMU Posted</option>
                        <option value="researcher_initiated" {{ old('initiation_type', $call->initiation_type) === 'researcher_initiated' ? 'selected' : '' }}>Researcher Initiated</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-control @error('status') is-invalid @enderror">
                        <option value="open"     {{ old('status', $call->status) === 'open'     ? 'selected' : '' }}>Open (visible to all)</option>
                        <option value="draft"    {{ old('status', $call->status) === 'draft'    ? 'selected' : '' }}>Draft (GMU only)</option>
                        <option value="closed"   {{ old('status', $call->status) === 'closed'   ? 'selected' : '' }}>Closed</option>
                        <option value="archived" {{ old('status', $call->status) === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Call Document / Guidelines</label>
                    @if($call->call_document)
                        <div class="mb-2">
                            <a href="{{ Storage::url($call->call_document) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-file mr-1"></i>Current Document
                            </a>
                            <small class="text-muted d-block mt-1">Upload a new file to replace it.</small>
                        </div>
                    @endif
                    <div class="custom-file">
                        <input type="file" name="call_document" class="custom-file-input" id="callDocument"
                               accept=".pdf,.doc,.docx">
                        <label class="custom-file-label" for="callDocument">Choose file (PDF/Word)</label>
                    </div>
                    <small class="text-muted">Max 10MB</small>
                </div>

            </div>
        </div>

        <div class="card bg-light">
            <div class="card-body">
                <p class="text-muted mb-1 small">
                    <i class="fas fa-clock mr-1"></i>
                    Posted {{ $call->created_at->format('d M Y') }} by {{ $call->postedBy->name }}
                </p>
                @if($call->interests->count() > 0)
                <p class="text-warning mb-0 small">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    {{ $call->interests->count() }} researcher(s) have already expressed interest.
                    Changing status to Closed/Archived will hide this call from them.
                </p>
                @endif
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save mr-1"></i> Save Changes
        </button>
        <a href="{{ route('calls.show', $call) }}" class="btn btn-default btn-block">Cancel</a>

    </div>
</div>

</form>

@endsection

@section('js')
<script>
document.querySelector('#callDocument').addEventListener('change', function(){
    var fileName = this.files[0]?.name || 'Choose file';
    this.nextElementSibling.textContent = fileName;
});
</script>
@endsection
