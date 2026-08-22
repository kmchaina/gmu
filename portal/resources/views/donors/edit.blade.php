@extends('adminlte::page')

@section('title', 'Edit Donor')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-edit mr-2"></i>Edit Donor</h1>
        <a href="{{ route('donors.show', $donor) }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
@endsection

@section('content')

<form action="{{ route('donors.update', $donor) }}" method="POST">
@csrf
@method('PUT')

<div class="row">
    <div class="col-md-8">

        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Donor Information</h3></div>
            <div class="card-body">

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $donor->name) }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="e.g. National Institutes of Health">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Acronym / Short Name</label>
                            <input type="text" name="acronym" value="{{ old('acronym', $donor->acronym) }}"
                                   class="form-control @error('acronym') is-invalid @enderror"
                                   placeholder="e.g. NIH" maxlength="20">
                            @error('acronym')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Donor Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-control @error('type') is-invalid @enderror">
                                <option value="">-- Select Type --</option>
                                <option value="bilateral"    {{ old('type', $donor->type) === 'bilateral'    ? 'selected' : '' }}>Bilateral (Government-to-Government)</option>
                                <option value="multilateral" {{ old('type', $donor->type) === 'multilateral' ? 'selected' : '' }}>Multilateral (UN, World Bank, etc.)</option>
                                <option value="foundation"   {{ old('type', $donor->type) === 'foundation'   ? 'selected' : '' }}>Foundation / Philanthropic</option>
                                <option value="government"   {{ old('type', $donor->type) === 'government'   ? 'selected' : '' }}>Government Agency</option>
                                <option value="private"      {{ old('type', $donor->type) === 'private'      ? 'selected' : '' }}>Private Sector</option>
                                <option value="other"        {{ old('type', $donor->type) === 'other'        ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Country / Region</label>
                            <input type="text" name="country" value="{{ old('country', $donor->country) }}"
                                   class="form-control" placeholder="e.g. United States, European Union">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Website</label>
                            <input type="url" name="website" value="{{ old('website', $donor->website) }}"
                                   class="form-control @error('website') is-invalid @enderror"
                                   placeholder="https://www.example.org">
                            @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Contact Email</label>
                            <input type="email" name="contact_email" value="{{ old('contact_email', $donor->contact_email) }}"
                                   class="form-control @error('contact_email') is-invalid @enderror"
                                   placeholder="grants@example.org">
                            @error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes / Additional Information</label>
                    <textarea name="notes" rows="4" class="form-control"
                              placeholder="Any relevant context about this donor — preferences, restrictions, relationship history...">{{ old('notes', $donor->notes) }}</textarea>
                </div>

            </div>
        </div>

    </div>

    <div class="col-md-4">

        <div class="card bg-light">
            <div class="card-body">
                <p class="text-muted small mb-1">
                    <i class="fas fa-link mr-1"></i>
                    <strong>{{ $donor->fundingCalls()->count() }}</strong> funding call(s) linked
                </p>
                <p class="text-muted small mb-0">
                    <i class="fas fa-trophy mr-1"></i>
                    <strong>{{ $donor->awards()->count() }}</strong> award(s) linked
                </p>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save mr-1"></i> Save Changes
        </button>
        <a href="{{ route('donors.show', $donor) }}" class="btn btn-default btn-block">Cancel</a>

        @if($donor->fundingCalls()->count() === 0 && $donor->awards()->count() === 0)
        <hr>
        <form action="{{ route('donors.destroy', $donor) }}" method="POST"
              onsubmit="return confirm('Delete this donor? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-block">
                <i class="fas fa-trash mr-1"></i> Delete Donor
            </button>
        </form>
        @endif

    </div>
</div>

</form>

@endsection
