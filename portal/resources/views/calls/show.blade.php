@extends('adminlte::page')

@section('title', $call->title)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-bullhorn mr-2"></i>{{ Str::limit($call->title, 60) }}</h1>
        <div>
            @if(auth()->user()->hasAnyRole(['coordinator','planning_officer']))
            <a href="{{ route('calls.edit', $call) }}" class="btn btn-warning">
                <i class="fas fa-edit mr-1"></i>Edit
            </a>
            @endif
            <a href="{{ route('calls.index') }}" class="btn btn-default ml-1">
                <i class="fas fa-arrow-left mr-1"></i>Back
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="row">

    {{-- Left: Call Details --}}
    <div class="col-md-8">

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Call Information</h3>
                <div class="card-tools">
                    @if($call->status === 'open') <span class="badge badge-success badge-lg">Open</span>
                    @elseif($call->status === 'closed') <span class="badge badge-danger badge-lg">Closed</span>
                    @elseif($call->status === 'draft') <span class="badge badge-warning badge-lg">Draft</span>
                    @else <span class="badge badge-secondary badge-lg">Archived</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="180" class="text-muted">Donor / Funder</th>
                        <td><strong>{{ $call->donor->name }}</strong>
                            @if($call->donor->acronym) ({{ $call->donor->acronym }}) @endif
                        </td>
                    </tr>
                    @if($call->solicitation_number)
                    <tr>
                        <th class="text-muted">Reference No.</th>
                        <td>{{ $call->solicitation_number }}</td>
                    </tr>
                    @endif
                    @if($call->theme)
                    <tr>
                        <th class="text-muted">Theme / Area</th>
                        <td><span class="badge badge-info">{{ $call->theme }}</span></td>
                    </tr>
                    @endif
                    <tr>
                        <th class="text-muted">Open Date</th>
                        <td>{{ $call->open_date ? $call->open_date->format('d F Y') : 'Not specified' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Submission Deadline</th>
                        <td>
                            <strong class="text-{{ $call->days_until_deadline <= 7 ? 'danger' : ($call->days_until_deadline <= 14 ? 'warning' : 'dark') }}">
                                {{ $call->deadline->format('d F Y') }}
                            </strong>
                            @if($call->status === 'open')
                                <span class="ml-2 badge badge-{{ $call->days_until_deadline <= 7 ? 'danger' : ($call->days_until_deadline <= 14 ? 'warning' : 'info') }}">
                                    {{ $call->days_until_deadline }} days remaining
                                </span>
                            @endif
                        </td>
                    </tr>
                    @if($call->estimated_budget)
                    <tr>
                        <th class="text-muted">Estimated Budget</th>
                        <td>{{ number_format($call->estimated_budget, 2) }} {{ $call->currency }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th class="text-muted">Initiation Type</th>
                        <td>
                            @if($call->initiation_type === 'gmu_posted')
                                <span class="badge badge-primary">GMU Posted</span>
                            @else
                                <span class="badge badge-secondary">Researcher Initiated</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted">Posted By</th>
                        <td>{{ $call->postedBy->name }} &bull; {{ $call->created_at->format('d M Y') }}</td>
                    </tr>
                </table>

                @if($call->description)
                <hr>
                <h6 class="font-weight-bold">Description</h6>
                <p>{{ $call->description }}</p>
                @endif

                @if($call->eligibility)
                <hr>
                <h6 class="font-weight-bold">Eligibility Requirements</h6>
                <p>{{ $call->eligibility }}</p>
                @endif

                @if($call->call_document)
                <hr>
                <a href="{{ route('calls.document', $call) }}" target="_blank" class="btn btn-outline-primary">
                    <i class="fas fa-file-pdf mr-1"></i> Download Call Document / Guidelines
                </a>
                @endif
            </div>
        </div>

        {{-- Interested Researchers (GMU/Coordinator view) --}}
        @if(auth()->user()->hasAnyRole(['coordinator','planning_officer']) && $call->interests->count() > 0)
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users mr-1"></i>
                    Interested Researchers ({{ $call->interests->count() }})
                </h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Researcher</th>
                            <th>Department</th>
                            <th>Submitted</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($call->interests as $interest)
                        <tr>
                            <td>
                                <input type="checkbox" class="researcher-check" name="selected[]"
                                       value="{{ $interest->user_id }}"
                                       {{ $interest->status === 'selected' ? 'checked' : '' }}>
                            </td>
                            <td>
                                <strong>{{ $interest->user->name }}</strong><br>
                                <small class="text-muted">{{ $interest->user->role?->display_name }}</small>
                            </td>
                            <td>{{ $interest->user->department ?? '-' }}</td>
                            <td>{{ $interest->created_at->format('d M Y') }}</td>
                            <td>
                                @if($interest->status === 'selected')
                                    <span class="badge badge-success">Selected</span>
                                @elseif($interest->status === 'not_selected')
                                    <span class="badge badge-danger">Not Selected</span>
                                @else
                                    <span class="badge badge-warning">Pending</span>
                                @endif
                            </td>
                        </tr>
                        <tr class="bg-light">
                            <td></td>
                            <td colspan="4">
                                <small>
                                    <strong>Motivation:</strong> {{ $interest->motivation }}<br>
                                    <strong>Experience:</strong> {{ $interest->relevant_experience }}
                                </small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Team Selection Form --}}
            @if($call->status === 'open')
            <div class="card-footer">
                <form action="{{ route('calls.select-team', $call) }}" method="POST" id="selectionForm">
                    @csrf
                    <div class="row align-items-end">
                        <div class="col-md-8">
                            <label>GMU Notes (reason for selection)</label>
                            <textarea name="gmu_notes" rows="2" class="form-control form-control-sm"
                                      placeholder="Reason for team selection..."></textarea>
                        </div>
                        <div class="col-md-4">
                            <button type="button" onclick="submitSelection()" class="btn btn-success btn-block mt-2">
                                <i class="fas fa-check mr-1"></i>Save Team Selection
                            </button>
                        </div>
                    </div>
                    <div id="hiddenInputs"></div>
                </form>
            </div>
            @endif
        </div>
        @endif

        {{-- Linked Proposals --}}
        @if($call->proposals->count() > 0)
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i>Proposals ({{ $call->proposals->count() }})</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Title</th><th>Lead PI</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach($call->proposals as $proposal)
                        <tr>
                            <td>{{ $proposal->title }}</td>
                            <td>{{ $proposal->leadPi->name ?? '-' }}</td>
                            <td><span class="badge badge-info">{{ ucfirst(str_replace('_',' ',$proposal->status)) }}</span></td>
                            <td><a href="{{ route('proposals.show', $proposal) }}" class="btn btn-xs btn-outline-primary">View</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>

    {{-- Right: Express Interest / Status --}}
    <div class="col-md-4">

        {{-- PI / Researcher: Express Interest --}}
        @if(auth()->user()->hasAnyRole(['pi','research_scientist']) && $call->status === 'open')
        <div class="card card-outline {{ $userInterest ? 'card-success' : 'card-warning' }}">
            <div class="card-header">
                <h3 class="card-title">
                    @if($userInterest)
                        <i class="fas fa-check-circle mr-1 text-success"></i>Your Application
                    @else
                        <i class="fas fa-hand-paper mr-1"></i>Express Interest
                    @endif
                </h3>
            </div>
            <div class="card-body">
                @if($userInterest)
                    <div class="alert alert-{{ $userInterest->status === 'selected' ? 'success' : ($userInterest->status === 'not_selected' ? 'danger' : 'warning') }} mb-0">
                        @if($userInterest->status === 'selected')
                            <i class="fas fa-check-circle mr-1"></i>
                            <strong>Congratulations!</strong> You have been selected for this proposal.
                        @elseif($userInterest->status === 'not_selected')
                            <i class="fas fa-times-circle mr-1"></i>
                            You were not selected for this call. The GMU will inform you of future opportunities.
                        @else
                            <i class="fas fa-clock mr-1"></i>
                            Your interest has been submitted and is under GMU review.
                        @endif
                    </div>
                    @if($userInterest->gmu_notes)
                        <p class="mt-2 text-muted small"><strong>GMU Note:</strong> {{ $userInterest->gmu_notes }}</p>
                    @endif
                @else
                    <p class="text-muted">Indicate your interest in applying for this grant. The GMU team will review all applicants and select the most suitable team.</p>
                    <form action="{{ route('calls.interest', $call) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Why are you interested? <span class="text-danger">*</span></label>
                            <textarea name="motivation" rows="3" class="form-control @error('motivation') is-invalid @enderror"
                                      placeholder="Describe why this call aligns with your research... (min 50 characters)"
                                      required minlength="50">{{ old('motivation') }}</textarea>
                            @error('motivation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label>Relevant Experience <span class="text-danger">*</span></label>
                            <textarea name="relevant_experience" rows="3" class="form-control @error('relevant_experience') is-invalid @enderror"
                                      placeholder="Your relevant publications, projects, expertise... (min 30 characters)"
                                      required minlength="30">{{ old('relevant_experience') }}</textarea>
                            @error('relevant_experience')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-warning btn-block">
                            <i class="fas fa-paper-plane mr-1"></i>Submit Interest
                        </button>
                    </form>
                @endif
            </div>
        </div>
        @endif

        {{-- Call Stats Box --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Quick Stats</h3></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Interested Researchers</span>
                        <strong>{{ $call->interests->count() }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Selected</span>
                        <strong class="text-success">{{ $call->interests->where('status','selected')->count() }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Linked Proposals</span>
                        <strong>{{ $call->proposals->count() }}</strong>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</div>
@endsection

@section('js')
<script>
document.getElementById('selectAll')?.addEventListener('change', function(){
    document.querySelectorAll('.researcher-check').forEach(cb => cb.checked = this.checked);
});

function submitSelection(){
    const form = document.getElementById('selectionForm');
    const container = document.getElementById('hiddenInputs');
    container.innerHTML = '';
    document.querySelectorAll('.researcher-check:checked').forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_user_ids[]';
        input.value = cb.value;
        container.appendChild(input);
    });
    form.submit();
}
</script>
@endsection
