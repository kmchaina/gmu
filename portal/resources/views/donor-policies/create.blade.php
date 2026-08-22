@extends('adminlte::page')

@section('title', 'Upload Policy')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-upload mr-2"></i>Upload Donor Policy</h1>
        <a href="{{ route('donor-policies.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
@endsection

@section('content')

<form action="{{ route('donor-policies.store') }}" method="POST" enctype="multipart/form-data">
@csrf

<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Policy Details</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label>Donor <span class="text-danger">*</span></label>
                    <select name="donor_id" class="form-control select2 @error('donor_id') is-invalid @enderror">
                        <option value="">-- Select Donor --</option>
                        @foreach($donors as $donor)
                            <option value="{{ $donor->id }}"
                                {{ old('donor_id', $selectedDonor?->id) == $donor->id ? 'selected' : '' }}>
                                {{ $donor->name }} @if($donor->acronym)({{ $donor->acronym }})@endif
                            </option>
                        @endforeach
                    </select>
                    @error('donor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}"
                           class="form-control @error('title') is-invalid @enderror"
                           placeholder="e.g. NIH Grants Policy Statement 2025">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Policy Type <span class="text-danger">*</span></label>
                            <select name="policy_type" class="form-control @error('policy_type') is-invalid @enderror">
                                <option value="">-- Select Type --</option>
                                <option value="grant_guidelines"   {{ old('policy_type') === 'grant_guidelines'   ? 'selected' : '' }}>Grant Guidelines</option>
                                <option value="reporting_template" {{ old('policy_type') === 'reporting_template' ? 'selected' : '' }}>Reporting Template</option>
                                <option value="budget_template"    {{ old('policy_type') === 'budget_template'    ? 'selected' : '' }}>Budget Template</option>
                                <option value="compliance_policy"  {{ old('policy_type') === 'compliance_policy'  ? 'selected' : '' }}>Compliance Policy</option>
                                <option value="other"              {{ old('policy_type') === 'other'              ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('policy_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Version</label>
                            <input type="number" name="version" value="{{ old('version', 1) }}" class="form-control" min="1">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Effective Date</label>
                            <input type="date" name="effective_date" value="{{ old('effective_date') }}" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="3" class="form-control" placeholder="Any notes about this document...">{{ old('notes') }}</textarea>
                </div>

                <div class="form-group">
                    <label>File <span class="text-danger">*</span></label>
                    <div class="custom-file">
                        <input type="file" name="file" class="custom-file-input @error('file') is-invalid @enderror"
                               id="policyFile" accept=".pdf,.doc,.docx,.xlsx">
                        <label class="custom-file-label" for="policyFile">Choose file (PDF/Word/Excel)</label>
                    </div>
                    @error('file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    <small class="text-muted">Max 20MB</small>
                </div>

            </div>
        </div>
    </div>

    <div class="col-md-4">
        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-upload mr-1"></i> Upload Policy
        </button>
        <a href="{{ route('donor-policies.index') }}" class="btn btn-default btn-block">Cancel</a>
    </div>
</div>

</form>

@endsection

@section('js')
<script>
document.querySelector('#policyFile')?.addEventListener('change', function(){
    this.nextElementSibling.textContent = this.files[0]?.name || 'Choose file';
});
</script>
@endsection
