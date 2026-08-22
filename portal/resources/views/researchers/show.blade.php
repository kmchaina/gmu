@extends('adminlte::page')

@section('title', $user->name . ' – Profile')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-user-circle mr-2"></i>{{ $user->name }}</h1>
        @if($canEdit)
            <a href="{{ route('researchers.edit', $user) }}" class="btn btn-sm btn-primary">
                <i class="fas fa-edit mr-1"></i>Edit Profile
            </a>
        @endif
    </div>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
    </div>
@endif

<div class="row">
    {{-- Left column: Profile card --}}
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-body text-center pt-4">
                <div class="mb-3">
                    <span class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                          style="width:80px;height:80px;font-size:2rem">
                        {{ strtoupper(substr($user->name,0,1)) }}
                    </span>
                </div>
                <h4 class="mb-0">{{ $profile?->title ? $profile->title . ' ' : '' }}{{ $user->name }}</h4>
                <p class="text-muted small mb-2">{{ $profile?->position ?? $user->role?->name }}</p>

                @if($profile?->department)
                    <p class="small mb-1"><i class="fas fa-building mr-1 text-muted"></i>{{ $profile->department }}</p>
                @endif
                @if($profile?->research_center)
                    <p class="small mb-1"><i class="fas fa-flask mr-1 text-muted"></i>{{ $profile->research_center }}</p>
                @endif
                @if($user->email)
                    <p class="small mb-1"><i class="fas fa-envelope mr-1 text-muted"></i>{{ $user->email }}</p>
                @endif
                @if($profile?->phone)
                    <p class="small mb-1"><i class="fas fa-phone mr-1 text-muted"></i>{{ $profile->phone }}</p>
                @endif
                @if($profile?->orcid_id)
                    <p class="small mb-1">
                        <img src="https://orcid.org/sites/default/files/images/orcid_16x16.png" alt="ORCID" class="mr-1">
                        <a href="https://orcid.org/{{ $profile->orcid_id }}" target="_blank">{{ $profile->orcid_id }}</a>
                    </p>
                @endif

                <div class="d-flex justify-content-center gap-2 mt-2">
                    @if($profile?->linkedin_url)
                        <a href="{{ $profile->linkedin_url }}" target="_blank" class="btn btn-xs btn-outline-primary">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    @endif
                    @if($profile?->researchgate_url)
                        <a href="{{ $profile->researchgate_url }}" target="_blank" class="btn btn-xs btn-outline-success">
                            RG
                        </a>
                    @endif
                </div>
            </div>

            <div class="card-footer p-0">
                <div class="row text-center">
                    <div class="col border-right py-3">
                        <div class="font-weight-bold text-primary" style="font-size:1.4rem">
                            {{ $profile?->total_grants_led ?? 0 }}
                        </div>
                        <small class="text-muted">Led</small>
                    </div>
                    <div class="col border-right py-3">
                        <div class="font-weight-bold text-info" style="font-size:1.4rem">
                            {{ $profile?->total_grants_contributed ?? 0 }}
                        </div>
                        <small class="text-muted">Contributed</small>
                    </div>
                    <div class="col py-3">
                        <div class="font-weight-bold text-success" style="font-size:1rem">
                            {{ number_format($profile?->total_funding_secured ?? 0) }}
                        </div>
                        <small class="text-muted">Funding</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expertise --}}
        @if($profile?->expertise_areas)
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title">Expertise Areas</h3></div>
            <div class="card-body">
                @foreach($profile->expertise_areas as $area)
                    <span class="badge badge-info mr-1 mb-1">{{ $area }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Qualifications --}}
        @if($profile?->qualifications)
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Qualifications</h3></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($profile->qualifications as $q)
                    <li class="list-group-item py-2">
                        <strong>{{ $q['degree'] }}</strong> – {{ $q['field'] ?? '' }}<br>
                        <small class="text-muted">{{ $q['institution'] ?? '' }}{{ isset($q['year']) ? ', '.$q['year'] : '' }}</small>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>

    {{-- Right column --}}
    <div class="col-md-8">

        {{-- Bio --}}
        @if($profile?->bio)
        <div class="card card-outline card-light">
            <div class="card-header"><h3 class="card-title">About</h3></div>
            <div class="card-body">{{ $profile->bio }}</div>
        </div>
        @endif

        {{-- Grant History --}}
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Grant History</h3></div>
            <div class="card-body p-0">
                @forelse($proposals as $proposal)
                <div class="d-flex align-items-start px-3 py-2 border-bottom">
                    <div class="flex-grow-1">
                        <a href="{{ route('proposals.show', $proposal) }}" class="font-weight-bold">{{ $proposal->title }}</a><br>
                        <small class="text-muted">
                            {{ $proposal->fundingCall?->donor?->acronym ?? 'N/A' }} &middot;
                            {{ ucfirst(str_replace('_',' ',$proposal->status)) }}
                            @if($proposal->award)
                                &middot; <span class="text-success">Awarded</span>
                            @endif
                        </small>
                    </div>
                    <span class="badge badge-{{ $proposal->status === 'approved' ? 'success' : ($proposal->status === 'declined' ? 'danger' : 'secondary') }} ml-2 mt-1">
                        {{ ucfirst($proposal->status) }}
                    </span>
                </div>
                @empty
                <p class="text-muted text-center py-3">No proposals yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Publications --}}
        @if($profile?->publications)
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title">Publications</h3></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($profile->publications as $pub)
                    <li class="list-group-item py-2">
                        <strong>{{ $pub['title'] }}</strong><br>
                        <small class="text-muted">
                            {{ $pub['journal'] ?? '' }}{{ isset($pub['year']) ? ' ('.$pub['year'].')' : '' }}
                            @if(!empty($pub['doi']))
                                &middot; <a href="https://doi.org/{{ $pub['doi'] }}" target="_blank">DOI</a>
                            @endif
                        </small>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        {{-- FCOI Disclosures — confidential to the researcher and the Coordinator (finding H3) --}}
        @if($canSeeFcoi)
        <div class="card card-outline card-warning">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">FCOI Disclosures</h3>
                @if($canEdit)
                <button class="btn btn-xs btn-warning" data-toggle="collapse" data-target="#fcoiForm">
                    + New Disclosure
                </button>
                @endif
            </div>

            @if($canEdit)
            <div class="collapse" id="fcoiForm">
                <form method="POST" action="{{ route('researchers.fcoi.store', $user) }}" class="card-body border-bottom">
                    @csrf
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Year</label>
                                <input type="number" name="disclosure_year" class="form-control form-control-sm"
                                       value="{{ date('Y') }}" min="2000" max="2099">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Linked Proposal <small class="text-muted">(optional)</small></label>
                                <select name="proposal_id" class="form-control form-control-sm">
                                    <option value="">— Annual / Not linked —</option>
                                    @foreach($proposals as $p)
                                        <option value="{{ $p->id }}">{{ Str::limit($p->title, 50) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Do you have a financial conflict of interest?</label>
                                <select name="has_conflict" id="hasConflict" class="form-control form-control-sm"
                                        onchange="document.getElementById('conflictDetail').style.display=this.value=='1'?'block':'none'">
                                    <option value="0">No — I declare no conflict</option>
                                    <option value="1">Yes — I have a conflict to disclose</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="conflictDetail" style="display:none">
                        <div class="form-group">
                            <label>Describe the conflict</label>
                            <textarea name="conflict_description" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Proposed management plan</label>
                            <textarea name="management_plan" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-warning">Submit Disclosure</button>
                </form>
            </div>
            @endif

            <div class="card-body p-0">
                @forelse($fcois as $fcoi)
                <div class="d-flex align-items-start px-3 py-2 border-bottom">
                    <div class="flex-grow-1">
                        <strong>{{ $fcoi->disclosure_year }}</strong>
                        @if($fcoi->proposal)
                            – {{ Str::limit($fcoi->proposal->title, 40) }}
                        @else
                            – Annual
                        @endif
                        <br>
                        <small class="text-muted">
                            {{ $fcoi->has_conflict ? 'Conflict declared' : 'No conflict' }}
                            @if($fcoi->reviewer_notes)
                                &middot; {{ $fcoi->reviewer_notes }}
                            @endif
                        </small>
                        @if($fcoi->has_conflict && $fcoi->conflict_description)
                            <div class="small mt-1">{{ $fcoi->conflict_description }}</div>
                        @endif
                        @if($fcoi->management_plan)
                            <div class="small text-muted mt-1"><em>Management plan:</em> {{ $fcoi->management_plan }}</div>
                        @endif
                    </div>
                    <div class="ml-2 text-right">
                        @php $b = match($fcoi->status){ 'cleared'=>'success','reviewed'=>'info','submitted'=>'warning',default=>'secondary' }; @endphp
                        <span class="badge badge-{{ $b }}">{{ ucfirst($fcoi->status) }}</span>
                        @if(auth()->user()->hasRole('coordinator') && in_array($fcoi->status, ['submitted','reviewed']))
                            <form method="POST" action="{{ route('fcoi.review', $fcoi) }}" class="mt-1">
                                @csrf @method('PATCH')
                                <div class="input-group input-group-sm">
                                    <select name="status" class="form-control form-control-sm">
                                        <option value="reviewed">Mark Reviewed</option>
                                        <option value="cleared">Clear</option>
                                    </select>
                                    <div class="input-group-append">
                                        <button class="btn btn-xs btn-outline-secondary">Go</button>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-center text-muted py-3">No FCOI disclosures on record.</p>
                @endforelse
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
