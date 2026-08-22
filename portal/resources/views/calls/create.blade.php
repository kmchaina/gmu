@extends('adminlte::page')

@section('title', 'Post New Funding Call')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-plus-circle mr-2"></i>Post New Funding Call</h1>
        <a href="{{ route('calls.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
@endsection

@section('content')

<form action="{{ route('calls.store') }}" method="POST" enctype="multipart/form-data">
@csrf

<div class="row">
    <div class="col-md-8">

        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Call Details</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label>Call Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}"
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
                                    <option value="{{ $donor->id }}" {{ old('donor_id') == $donor->id ? 'selected' : '' }}>
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
                            <input type="text" name="solicitation_number" value="{{ old('solicitation_number') }}"
                                   class="form-control" placeholder="e.g. RFA-TW-26-001">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Research Theme / Area</label>
                    <input type="text" name="theme" value="{{ old('theme') }}"
                           class="form-control" placeholder="e.g. Infectious Diseases, Maternal Health, Nutrition...">
                </div>

                <div class="form-group">
                    <label>Description / Summary</label>
                    <textarea name="description" rows="4" class="form-control"
                              placeholder="Describe the funding opportunity, objectives, and scope...">{{ old('description') }}</textarea>
                </div>

                <div class="form-group">
                    <label>Eligibility Requirements</label>
                    <textarea name="eligibility" rows="3" class="form-control"
                              placeholder="Who can apply? Institutions, qualifications, countries...">{{ old('eligibility') }}</textarea>
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
                            <input type="number" name="estimated_budget" value="{{ old('estimated_budget') }}"
                                   class="form-control" placeholder="0.00" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-2">
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
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Open Date</label>
                            <input type="date" name="open_date" value="{{ old('open_date') }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Submission Deadline <span class="text-danger">*</span></label>
                            <input type="date" name="deadline" value="{{ old('deadline') }}"
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
                        <option value="gmu_posted"           {{ old('initiation_type','gmu_posted') === 'gmu_posted' ? 'selected' : '' }}>GMU Posted</option>
                        <option value="researcher_initiated" {{ old('initiation_type') === 'researcher_initiated' ? 'selected' : '' }}>Researcher Initiated</option>
                    </select>
                    <small class="text-muted">GMU Posted = GMU found this call. Researcher Initiated = team brought it to GMU.</small>
                </div>

                <div class="form-group">
                    <label>Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-control @error('status') is-invalid @enderror">
                        <option value="open"   {{ old('status','open') === 'open'  ? 'selected' : '' }}>Open (visible to all)</option>
                        <option value="draft"  {{ old('status') === 'draft'        ? 'selected' : '' }}>Draft (GMU only)</option>
                        <option value="closed" {{ old('status') === 'closed'       ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Call Document / Guidelines</label>
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
                <p class="text-muted mb-2"><i class="fas fa-info-circle mr-1"></i>After posting:</p>
                <ul class="text-muted small pl-3">
                    <li>All researchers will receive an alert email</li>
                    <li>The call will appear in the weekly digest</li>
                    <li>Researchers can express interest from the call page</li>
                    <li>You will select the team from interested researchers</li>
                </ul>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-paper-plane mr-1"></i> Post Funding Call
        </button>
        <a href="{{ route('calls.index') }}" class="btn btn-default btn-block">Cancel</a>

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
