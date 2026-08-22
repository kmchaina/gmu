@extends('adminlte::page')

@section('title', 'Edit Policy')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-edit mr-2"></i>Edit Policy Document</h1>
        <a href="{{ route('donor-policies.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
@endsection

@section('content')

<form action="{{ route('donor-policies.update', $donorPolicy) }}" method="POST" enctype="multipart/form-data">
@csrf
@method('PUT')

<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Policy Details</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label>Donor <span class="text-danger">*</span></label>
                    <select name="donor_id" class="form-control select2 @error('donor_id') is-invalid @enderror">
                        @foreach($donors as $donor)
                            <option value="{{ $donor->id }}" {{ old('donor_id', $donorPolicy->donor_id) == $donor->id ? 'selected' : '' }}>
                                {{ $donor->name }} @if($donor->acronym)({{ $donor->acronym }})@endif
                            </option>
                        @endforeach
                    </select>
                    @error('donor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $donorPolicy->title) }}"
                           class="form-control @error('title') is-invalid @enderror">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Policy Type</label>
                            <select name="policy_type" class="form-control">
                                <option value="grant_guidelines"   {{ old('policy_type', $donorPolicy->policy_type) === 'grant_guidelines'   ? 'selected' : '' }}>Grant Guidelines</option>
                                <option value="reporting_template" {{ old('policy_type', $donorPolicy->policy_type) === 'reporting_template' ? 'selected' : '' }}>Reporting Template</option>
                                <option value="budget_template"    {{ old('policy_type', $donorPolicy->policy_type) === 'budget_template'    ? 'selected' : '' }}>Budget Template</option>
                                <option value="compliance_policy"  {{ old('policy_type', $donorPolicy->policy_type) === 'compliance_policy'  ? 'selected' : '' }}>Compliance Policy</option>
                                <option value="other"              {{ old('policy_type', $donorPolicy->policy_type) === 'other'              ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Version</label>
                            <input type="number" name="version" value="{{ old('version', $donorPolicy->version) }}" class="form-control" min="1">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Effective Date</label>
                            <input type="date" name="effective_date"
                                   value="{{ old('effective_date', $donorPolicy->effective_date?->format('Y-m-d')) }}"
                                   class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="3" class="form-control">{{ old('notes', $donorPolicy->notes) }}</textarea>
                </div>

                <div class="form-group">
                    <label>Replace File</label>
                    <p>
                        <a href="{{ Storage::url($donorPolicy->file_path) }}" target="_blank" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-download mr-1"></i>{{ $donorPolicy->file_name }}
                        </a>
                    </p>
                    <div class="custom-file">
                        <input type="file" name="file" class="custom-file-input" id="policyFile" accept=".pdf,.doc,.docx,.xlsx">
                        <label class="custom-file-label" for="policyFile">Choose replacement file</label>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="col-md-4">
        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save mr-1"></i> Save Changes
        </button>
        <a href="{{ route('donor-policies.index') }}" class="btn btn-default btn-block">Cancel</a>

        <hr>
        <form action="{{ route('donor-policies.destroy', $donorPolicy) }}" method="POST"
              onsubmit="return confirm('Delete this policy document?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-block btn-sm">
                <i class="fas fa-trash mr-1"></i>Delete
            </button>
        </form>
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
