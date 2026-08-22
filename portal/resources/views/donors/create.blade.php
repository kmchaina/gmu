@extends('adminlte::page')

@section('title', 'Add Donor')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-plus-circle mr-2"></i>Add Donor / Funder</h1>
        <a href="{{ route('donors.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
@endsection

@section('content')

<form action="{{ route('donors.store') }}" method="POST">
@csrf

<div class="row">
    <div class="col-md-8">

        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Donor Information</h3></div>
            <div class="card-body">

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="e.g. National Institutes of Health">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Acronym / Short Name</label>
                            <input type="text" name="acronym" value="{{ old('acronym') }}"
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
                                <option value="bilateral"    {{ old('type') === 'bilateral'    ? 'selected' : '' }}>Bilateral (Government-to-Government)</option>
                                <option value="multilateral" {{ old('type') === 'multilateral' ? 'selected' : '' }}>Multilateral (UN, World Bank, etc.)</option>
                                <option value="foundation"   {{ old('type') === 'foundation'   ? 'selected' : '' }}>Foundation / Philanthropic</option>
                                <option value="government"   {{ old('type') === 'government'   ? 'selected' : '' }}>Government Agency</option>
                                <option value="private"      {{ old('type') === 'private'      ? 'selected' : '' }}>Private Sector</option>
                                <option value="other"        {{ old('type') === 'other'        ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Country / Region</label>
                            <input type="text" name="country" value="{{ old('country') }}"
                                   class="form-control" placeholder="e.g. United States, European Union">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Website</label>
                            <input type="url" name="website" value="{{ old('website') }}"
                                   class="form-control @error('website') is-invalid @enderror"
                                   placeholder="https://www.example.org">
                            @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Contact Email</label>
                            <input type="email" name="contact_email" value="{{ old('contact_email') }}"
                                   class="form-control @error('contact_email') is-invalid @enderror"
                                   placeholder="grants@example.org">
                            @error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes / Additional Information</label>
                    <textarea name="notes" rows="4" class="form-control"
                              placeholder="Any relevant context about this donor — preferences, restrictions, relationship history...">{{ old('notes') }}</textarea>
                </div>

            </div>
        </div>

    </div>

    <div class="col-md-4">

        <div class="card bg-light">
            <div class="card-body">
                <p class="font-weight-bold mb-2"><i class="fas fa-info-circle mr-1 text-primary"></i>Donor Types</p>
                <dl class="small text-muted">
                    <dt>Bilateral</dt><dd>Direct government-to-government funding (e.g. USAID, DFID)</dd>
                    <dt>Multilateral</dt><dd>International organizations (e.g. WHO, UNICEF, World Bank)</dd>
                    <dt>Foundation</dt><dd>Private philanthropy (e.g. Bill & Melinda Gates, Wellcome Trust)</dd>
                    <dt>Government</dt><dd>National government agencies (e.g. NIH, MRC)</dd>
                    <dt>Private</dt><dd>Corporate or industry funding</dd>
                </dl>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save mr-1"></i> Save Donor
        </button>
        <a href="{{ route('donors.index') }}" class="btn btn-default btn-block">Cancel</a>

    </div>
</div>

</form>

@endsection
