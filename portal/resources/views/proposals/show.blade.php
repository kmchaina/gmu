@extends('adminlte::page')

@section('title', $proposal->title)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-file-alt mr-2"></i>{{ Str::limit($proposal->title, 60) }}</h1>
        <div>
            @if(auth()->user()->hasAnyRole(['coordinator','planning_officer']))
            <a href="{{ route('proposals.edit', $proposal) }}" class="btn btn-warning">
                <i class="fas fa-edit mr-1"></i>Edit
            </a>
            @endif
            <a href="{{ route('proposals.index') }}" class="btn btn-default ml-1">
                <i class="fas fa-arrow-left mr-1"></i>Back
            </a>
        </div>
    </div>
@endsection

@section('content')

@php
    $statusColors = [
        'concept'           => 'secondary',
        'under_development' => 'info',
        'internal_review'   => 'warning',
        'approved'          => 'primary',
        'submitted'         => 'dark',
        'awarded'           => 'success',
        'declined'          => 'danger',
        'withdrawn'         => 'light',
    ];
    $reviewTypeMap = [
        'research_scientist' => 'technical',
        'legal_officer'      => 'compliance',
        'finance_officer'    => 'budget',
    ];
    $userRole = auth()->user()->role?->name;
    $myReviewType = $reviewTypeMap[$userRole] ?? null;
@endphp

<div class="row">

    {{-- Left: Proposal Details --}}
    <div class="col-md-8">

        {{-- Core Info --}}
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Proposal Information</h3>
                <div class="card-tools">
                    <span class="badge badge-{{ $statusColors[$proposal->status] ?? 'secondary' }} badge-lg">
                        {{ ucfirst(str_replace('_', ' ', $proposal->status)) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="200" class="text-muted">Funding Call</th>
                        <td>
                            <a href="{{ route('calls.show', $proposal->fundingCall) }}">
                                {{ $proposal->fundingCall->title }}
                            </a><br>
                            <small class="text-muted">{{ $proposal->fundingCall->donor->name }}</small>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted">Lead PI</th>
                        <td><strong>{{ $proposal->leadPi->name ?? '-' }}</strong></td>
                    </tr>
                    @if($proposal->assignedOfficer)
                    <tr>
                        <th class="text-muted">GMU Officer</th>
                        <td>{{ $proposal->assignedOfficer->name }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th class="text-muted">Initiation Type</th>
                        <td>
                            @if($proposal->initiation_type === 'gmu_initiated')
                                <span class="badge badge-primary">GMU Initiated</span>
                            @else
                                <span class="badge badge-secondary">Researcher Initiated</span>
                            @endif
                        </td>
                    </tr>
                    @if($proposal->requested_budget)
                    <tr>
                        <th class="text-muted">Requested Budget</th>
                        <td>{{ number_format($proposal->requested_budget, 2) }} {{ $proposal->currency }}</td>
                    </tr>
                    @endif
                    @if($proposal->target_submission_date)
                    <tr>
                        <th class="text-muted">Target Submission</th>
                        <td>{{ $proposal->target_submission_date->format('d F Y') }}</td>
                    </tr>
                    @endif
                    @if($proposal->submitted_at)
                    <tr>
                        <th class="text-muted">Submitted To Donor</th>
                        <td>{{ $proposal->submitted_at->format('d F Y') }}</td>
                    </tr>
                    @endif
                    @if($proposal->outcome_date)
                    <tr>
                        <th class="text-muted">Outcome Date</th>
                        <td>{{ $proposal->outcome_date->format('d F Y') }}</td>
                    </tr>
                    @endif
                </table>

                @if($proposal->abstract)
                <hr>
                <h6 class="font-weight-bold">Abstract</h6>
                <p>{{ $proposal->abstract }}</p>
                @endif

                @if($proposal->outcome_notes)
                <hr>
                <h6 class="font-weight-bold">Outcome Notes</h6>
                <div class="alert alert-{{ $proposal->status === 'awarded' ? 'success' : 'danger' }} mb-0">
                    {{ $proposal->outcome_notes }}
                </div>
                @endif
            </div>
        </div>

        {{-- Internal Review Panel --}}
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clipboard-check mr-1"></i>Internal Reviews</h3>
                <div class="card-tools">
                    @if($proposal->isReviewComplete())
                        <span class="badge badge-success">All Reviews Complete</span>
                    @else
                        <span class="badge badge-warning">{{ $proposal->reviews->where('status','approved')->count() }}/3 Approved</span>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Review Type</th>
                            <th>Reviewer</th>
                            <th>Status</th>
                            <th>Score</th>
                            <th>Reviewed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(['technical' => 'Technical', 'compliance' => 'Compliance', 'budget' => 'Budget'] as $type => $label)
                        @php $review = $proposal->reviews->where('review_type', $type)->first(); @endphp
                        <tr>
                            <td><strong>{{ $label }}</strong></td>
                            <td>{{ $review?->reviewer?->name ?? '<span class="text-muted">Unassigned</span>' }}</td>
                            <td>
                                @if(!$review || $review->status === 'pending')
                                    <span class="badge badge-secondary">Pending</span>
                                @elseif($review->status === 'approved')
                                    <span class="badge badge-success">Approved</span>
                                @elseif($review->status === 'rejected')
                                    <span class="badge badge-danger">Rejected</span>
                                @elseif($review->status === 'needs_revision')
                                    <span class="badge badge-warning">Needs Revision</span>
                                @else
                                    <span class="badge badge-info">In Progress</span>
                                @endif
                            </td>
                            <td>{{ $review?->score ? $review->score . '/10' : '-' }}</td>
                            <td>{{ $review?->reviewed_at?->format('d M Y') ?? '-' }}</td>
                        </tr>
                        @if($review?->comments)
                        <tr class="bg-light">
                            <td></td>
                            <td colspan="4"><small class="text-muted"><strong>Comment:</strong> {{ $review->comments }}</small></td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Submit Review Form (for assigned review roles) --}}
            @if($myReviewType && $proposal->status === 'internal_review')
            @php $myReview = $proposal->reviews->where('review_type', $myReviewType)->first(); @endphp
            <div class="card-footer">
                <h6 class="font-weight-bold mb-2">
                    <i class="fas fa-pencil-alt mr-1"></i>
                    Submit Your {{ ucfirst($myReviewType) }} Review
                </h6>
                <form action="{{ route('proposals.review', $proposal) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Decision</label>
                                <select name="status" class="form-control form-control-sm">
                                    <option value="approved"       {{ $myReview?->status === 'approved'       ? 'selected' : '' }}>Approve</option>
                                    <option value="needs_revision" {{ $myReview?->status === 'needs_revision' ? 'selected' : '' }}>Needs Revision</option>
                                    <option value="rejected"       {{ $myReview?->status === 'rejected'       ? 'selected' : '' }}>Reject</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Score (1-10)</label>
                                <input type="number" name="score" min="1" max="10" value="{{ $myReview?->score }}"
                                       class="form-control form-control-sm" placeholder="e.g. 8">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Comments <span class="text-danger">*</span></label>
                                <textarea name="comments" rows="2" class="form-control form-control-sm @error('comments') is-invalid @enderror"
                                          placeholder="Your review comments (min 20 chars)...">{{ old('comments', $myReview?->comments) }}</textarea>
                                @error('comments')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-check mr-1"></i>Submit Review
                    </button>
                </form>
            </div>
            @endif
        </div>

        {{-- Team Members --}}
        @if($proposal->teamMembers->count() > 0)
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-users mr-1"></i>Research Team</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Name</th><th>Role</th><th>Department</th></tr>
                    </thead>
                    <tbody>
                        @foreach($proposal->teamMembers as $member)
                        <tr>
                            <td><strong>{{ $member->user->name }}</strong></td>
                            <td><span class="badge badge-secondary">{{ ucfirst(str_replace('_',' ',$member->role)) }}</span></td>
                            <td>{{ $member->user->department ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>

    {{-- Right: Actions & Stats --}}
    <div class="col-md-4">

        {{-- Status Progress --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Proposal Pipeline</h3></div>
            <div class="card-body p-0">
                @php
                    $stages = ['concept','under_development','internal_review','approved','submitted','awarded'];
                    $currentIdx = array_search($proposal->status, $stages);
                @endphp
                <ul class="list-group list-group-flush">
                    @foreach($stages as $idx => $stage)
                    <li class="list-group-item py-2
                        {{ $currentIdx !== false && $idx < $currentIdx ? 'text-muted' : '' }}
                        {{ $proposal->status === $stage ? 'active' : '' }}">
                        @if($currentIdx !== false && $idx < $currentIdx)
                            <i class="fas fa-check-circle text-success mr-2"></i>
                        @elseif($proposal->status === $stage)
                            <i class="fas fa-dot-circle text-white mr-2"></i>
                        @else
                            <i class="far fa-circle text-muted mr-2"></i>
                        @endif
                        {{ ucfirst(str_replace('_', ' ', $stage)) }}
                    </li>
                    @endforeach
                    @if(in_array($proposal->status, ['declined','withdrawn']))
                    <li class="list-group-item py-2 text-danger active">
                        <i class="fas fa-times-circle mr-2"></i>
                        {{ ucfirst($proposal->status) }}
                    </li>
                    @endif
                </ul>
            </div>
        </div>

        {{-- Award Link --}}
        @if($proposal->award)
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-trophy mr-1"></i>Award</h3></div>
            <div class="card-body">
                <p class="mb-1">{{ Str::limit($proposal->award->project_title, 50) }}</p>
                <p class="mb-2 text-muted small">{{ number_format($proposal->award->total_budget, 0) }} {{ $proposal->award->currency }}</p>
                <a href="{{ route('awards.show', $proposal->award) }}" class="btn btn-success btn-sm btn-block">
                    <i class="fas fa-eye mr-1"></i>View Award
                </a>
            </div>
        </div>
        @elseif($proposal->status === 'approved' && auth()->user()->hasAnyRole(['coordinator','planning_officer']))
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title">Create Award</h3></div>
            <div class="card-body">
                <p class="text-muted small">This proposal is approved. If the donor confirms funding, you can create an award.</p>
                <a href="{{ route('awards.create') }}?proposal_id={{ $proposal->id }}" class="btn btn-success btn-block btn-sm">
                    <i class="fas fa-plus mr-1"></i>Create Award
                </a>
            </div>
        </div>
        @endif

        {{-- Linked Call Quick Info --}}
        <div class="card bg-light">
            <div class="card-body">
                <p class="small text-muted mb-1"><i class="fas fa-calendar mr-1"></i>Call Deadline:
                    <strong>{{ $proposal->fundingCall->deadline->format('d M Y') }}</strong>
                </p>
                <p class="small text-muted mb-0"><i class="fas fa-clock mr-1"></i>Registered:
                    <strong>{{ $proposal->created_at->format('d M Y') }}</strong>
                </p>
            </div>
        </div>

    </div>
</div>

@endsection
