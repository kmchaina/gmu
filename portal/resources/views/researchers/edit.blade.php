@extends('adminlte::page')

@section('title', 'Edit Profile – ' . $user->name)

@section('content_header')
    <h1><i class="fas fa-user-edit mr-2"></i>Edit Profile – {{ $user->name }}</h1>
@endsection

@section('content')
<form method="POST" action="{{ route('researchers.update', $user) }}">
    @csrf @method('PUT')

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Basic Info --}}
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">Professional Information</h3></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Title</label>
                        <select name="title" class="form-control form-control-sm">
                            <option value="">—</option>
                            @foreach(['Dr.','Prof.','Mr.','Ms.','Mrs.'] as $t)
                                <option value="{{ $t }}" @selected(old('title', $profile->title) === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <label>Position / Job Title</label>
                        <input type="text" name="position" class="form-control form-control-sm"
                               value="{{ old('position', $profile->position) }}">
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department" class="form-control form-control-sm"
                               value="{{ old('department', $profile->department) }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Research Centre</label>
                        <input type="text" name="research_center" class="form-control form-control-sm"
                               value="{{ old('research_center', $profile->research_center) }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control form-control-sm"
                               value="{{ old('phone', $profile->phone) }}">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Biography / Research Summary <small class="text-muted">(max 3000 chars)</small></label>
                        <textarea name="bio" class="form-control form-control-sm" rows="4">{{ old('bio', $profile->bio) }}</textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Expertise Areas <small class="text-muted">comma-separated</small></label>
                        <input type="text" name="expertise_areas" class="form-control form-control-sm"
                               value="{{ old('expertise_areas', implode(', ', $profile->expertise_areas ?? [])) }}"
                               placeholder="Epidemiology, Malaria, One Health, Clinical Trials">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Links & ORCID --}}
    <div class="card card-outline card-info">
        <div class="card-header"><h3 class="card-title">Links & Identifiers</h3></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>ORCID iD <small class="text-muted">0000-0000-0000-0000</small></label>
                        <input type="text" name="orcid_id" class="form-control form-control-sm"
                               value="{{ old('orcid_id', $profile->orcid_id) }}"
                               placeholder="0000-0002-1825-0097">
                        @if($profile->orcid_id)
                            <small class="text-success"><i class="fas fa-check-circle"></i> ORCID recorded
                                @if($profile->orcid_synced_at)
                                    — synced {{ $profile->orcid_synced_at->diffForHumans() }}
                                @else
                                    (OAuth sync not yet configured)
                                @endif
                            </small>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>LinkedIn URL</label>
                        <input type="url" name="linkedin_url" class="form-control form-control-sm"
                               value="{{ old('linkedin_url', $profile->linkedin_url) }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>ResearchGate URL</label>
                        <input type="url" name="researchgate_url" class="form-control form-control-sm"
                               value="{{ old('researchgate_url', $profile->researchgate_url) }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Qualifications --}}
    <div class="card card-outline card-secondary">
        <div class="card-header d-flex justify-content-between">
            <h3 class="card-title">Qualifications</h3>
            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="addQual()">+ Add</button>
        </div>
        <div class="card-body" id="qualContainer">
            @foreach(old('qual_degree', array_column($profile->qualifications ?? [], 'degree')) as $i => $deg)
            <div class="row qual-row mb-2">
                <div class="col-md-2">
                    <input type="text" name="qual_degree[]" class="form-control form-control-sm" placeholder="Degree"
                           value="{{ $deg }}">
                </div>
                <div class="col-md-3">
                    <input type="text" name="qual_field[]" class="form-control form-control-sm" placeholder="Field"
                           value="{{ old('qual_field.'.$i, ($profile->qualifications[$i]['field'] ?? '')) }}">
                </div>
                <div class="col-md-4">
                    <input type="text" name="qual_institution[]" class="form-control form-control-sm" placeholder="Institution"
                           value="{{ old('qual_institution.'.$i, ($profile->qualifications[$i]['institution'] ?? '')) }}">
                </div>
                <div class="col-md-2">
                    <input type="number" name="qual_year[]" class="form-control form-control-sm" placeholder="Year"
                           value="{{ old('qual_year.'.$i, ($profile->qualifications[$i]['year'] ?? '')) }}">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-xs btn-outline-danger" onclick="this.closest('.qual-row').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Publications --}}
    <div class="card card-outline card-success">
        <div class="card-header d-flex justify-content-between">
            <h3 class="card-title">Publications</h3>
            <button type="button" class="btn btn-xs btn-outline-success" onclick="addPub()">+ Add</button>
        </div>
        <div class="card-body" id="pubContainer">
            @foreach(old('pub_title', array_column($profile->publications ?? [], 'title')) as $i => $title)
            <div class="row pub-row mb-2">
                <div class="col-md-5">
                    <input type="text" name="pub_title[]" class="form-control form-control-sm" placeholder="Title"
                           value="{{ $title }}">
                </div>
                <div class="col-md-3">
                    <input type="text" name="pub_journal[]" class="form-control form-control-sm" placeholder="Journal"
                           value="{{ old('pub_journal.'.$i, ($profile->publications[$i]['journal'] ?? '')) }}">
                </div>
                <div class="col-md-1">
                    <input type="number" name="pub_year[]" class="form-control form-control-sm" placeholder="Year"
                           value="{{ old('pub_year.'.$i, ($profile->publications[$i]['year'] ?? '')) }}">
                </div>
                <div class="col-md-2">
                    <input type="text" name="pub_doi[]" class="form-control form-control-sm" placeholder="DOI"
                           value="{{ old('pub_doi.'.$i, ($profile->publications[$i]['doi'] ?? '')) }}">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-xs btn-outline-danger" onclick="this.closest('.pub-row').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i>Save Profile
        </button>
        <a href="{{ route('researchers.show', $user) }}" class="btn btn-secondary ml-2">Cancel</a>
    </div>
</form>
@endsection

@section('js')
<script>
function addQual() {
    const row = `<div class="row qual-row mb-2">
        <div class="col-md-2"><input type="text" name="qual_degree[]" class="form-control form-control-sm" placeholder="Degree"></div>
        <div class="col-md-3"><input type="text" name="qual_field[]" class="form-control form-control-sm" placeholder="Field"></div>
        <div class="col-md-4"><input type="text" name="qual_institution[]" class="form-control form-control-sm" placeholder="Institution"></div>
        <div class="col-md-2"><input type="number" name="qual_year[]" class="form-control form-control-sm" placeholder="Year"></div>
        <div class="col-md-1"><button type="button" class="btn btn-xs btn-outline-danger" onclick="this.closest('.qual-row').remove()"><i class="fas fa-times"></i></button></div>
    </div>`;
    document.getElementById('qualContainer').insertAdjacentHTML('beforeend', row);
}
function addPub() {
    const row = `<div class="row pub-row mb-2">
        <div class="col-md-5"><input type="text" name="pub_title[]" class="form-control form-control-sm" placeholder="Title"></div>
        <div class="col-md-3"><input type="text" name="pub_journal[]" class="form-control form-control-sm" placeholder="Journal"></div>
        <div class="col-md-1"><input type="number" name="pub_year[]" class="form-control form-control-sm" placeholder="Year"></div>
        <div class="col-md-2"><input type="text" name="pub_doi[]" class="form-control form-control-sm" placeholder="DOI"></div>
        <div class="col-md-1"><button type="button" class="btn btn-xs btn-outline-danger" onclick="this.closest('.pub-row').remove()"><i class="fas fa-times"></i></button></div>
    </div>`;
    document.getElementById('pubContainer').insertAdjacentHTML('beforeend', row);
}
</script>
@endsection
