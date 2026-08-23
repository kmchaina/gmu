@extends('layouts.gmu')

@section('title', Str::limit($proposal->title, 40))
@section('crumb', 'Proposal')

@section('content')
@php
    use App\Models\ProposalApproval;

    $stages = ['concept', 'under_development', 'internal_review', 'approved', 'submitted', 'awarded'];
    $stageIndex = array_search($proposal->status, $stages, true);
    $tracks = ['technical' => 'Technical', 'compliance' => 'Compliance', 'budget' => 'Budget'];
    $drcp = $proposal->approvals->firstWhere('level', 'drcp');
    $dg   = $proposal->approvals->firstWhere('level', 'dg');
@endphp

<div class="page-head">
    <div style="min-width:0;">
        <a href="{{ route('proposals.index') }}" class="small">&larr; Proposals</a>
        <h1 style="margin-top:8px;">{{ $proposal->title }}</h1>
        <div class="row" style="gap:12px; margin-top:10px;">
            <x-ui.pill :tone="match($proposal->status) {
                'awarded' => 'ok', 'approved' => 'ok', 'declined' => 'danger',
                'internal_review' => 'warn', 'submitted' => 'navy', default => null }">
                {{ Str::headline($proposal->status) }}
            </x-ui.pill>
            <span class="small muted">
                {{ $proposal->fundingCall?->donor?->name }} · {{ Str::limit($proposal->fundingCall?->title, 44) }}
            </span>
        </div>
    </div>
    <div class="page-actions">
        @can('update', $proposal)
            <a href="{{ route('proposals.edit', $proposal) }}" class="btn"><i class="fas fa-pen"></i> Edit</a>
        @endcan
        @if($proposal->award)
            <a href="{{ route('awards.show', $proposal->award) }}" class="btn btn-primary"><i class="fas fa-trophy"></i> Open award</a>
        @elseif($proposal->isClearedForSubmission())
            @can('create', App\Models\Award::class)
                <a href="{{ route('awards.create', ['proposal_id' => $proposal->id]) }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Record the award
                </a>
            @endcan
        @endif
    </div>
</div>

<div class="stack">

    {{-- Pipeline --}}
    <x-ui.card>
        <div class="row" style="gap:0; align-items:stretch;">
            @foreach($stages as $i => $stage)
                @php
                    $done    = $stageIndex !== false && $i < $stageIndex;
                    $current = $proposal->status === $stage;
                @endphp
                <div style="flex:1; min-width:0; text-align:center; position:relative;">
                    <div style="height:4px; border-radius:2px; margin-bottom:12px;
                                background: {{ $done || $current ? 'var(--navy-500)' : 'var(--line)' }};"></div>
                    <div class="tiny {{ $current ? '' : 'muted-3' }}" style="{{ $current ? 'font-weight:600; color:var(--navy-700);' : '' }}">
                        {{ Str::headline($stage) }}
                    </div>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    <div class="grid grid-wide">
        <div class="stack">

            <x-ui.card title="Abstract">
                @if($proposal->abstract)
                    <p style="line-height:1.75; color:var(--muted);">{{ $proposal->abstract }}</p>
                @else
                    <p class="small muted-3">No abstract recorded yet.</p>
                @endif
            </x-ui.card>

            {{-- Internal review --}}
            <x-ui.card title="Internal review" note="Three tracks, each owned by the officer who holds it" :flush="true">
                <div class="list">
                    @foreach($tracks as $key => $label)
                        @php $review = $proposal->reviews->firstWhere('review_type', $key); @endphp
                        <div class="list-row" style="align-items:flex-start;">
                            <div class="list-main">
                                <span class="list-title">{{ $label }} review</span>
                                <span class="list-sub">
                                    @if($review?->reviewed_at)
                                        {{ $review->reviewer?->name }} · {{ $review->reviewed_at->format('d M Y') }}
                                        @if($review->score) · scored {{ $review->score }}/10 @endif
                                    @else
                                        Not yet filed
                                    @endif
                                </span>
                                @if($review?->comments)
                                    <p class="small muted" style="margin-top:8px; line-height:1.65;">{{ $review->comments }}</p>
                                @endif
                            </div>
                            <x-ui.pill :tone="match($review?->status) {
                                'approved' => 'ok', 'rejected' => 'danger', 'needs_revision' => 'warn', default => null }">
                                {{ $review ? Str::headline($review->status) : 'Pending' }}
                            </x-ui.pill>
                        </div>
                    @endforeach
                </div>

                @if($canReview && $userReview?->status !== 'approved')
                    <div class="card-body" style="background:var(--canvas); border-top:1px solid var(--line-soft);">
                        <div class="eyebrow" style="margin-bottom:12px;">File your {{ auth()->user()->reviewTrack() }} review</div>
                        <form method="POST" action="{{ route('proposals.review', $proposal) }}">
                            @csrf
                            <div class="form-grid">
                                <x-ui.field label="Decision" name="status" required>
                                    <select name="status" class="select" required>
                                        <option value="approved">Approve</option>
                                        <option value="needs_revision">Needs revision — send back to the team</option>
                                        <option value="rejected">Reject</option>
                                    </select>
                                </x-ui.field>
                                <x-ui.field label="Score (optional)" name="score">
                                    <input type="number" name="score" min="1" max="10" class="input">
                                </x-ui.field>
                                <x-ui.field label="Comments" name="comments" required wide help="At least 20 characters — the team will read this">
                                    <textarea name="comments" rows="3" class="textarea" required>{{ old('comments') }}</textarea>
                                </x-ui.field>
                            </div>
                            <div class="form-actions"><button class="btn btn-primary">Submit review</button></div>
                        </form>
                    </div>
                @endif
            </x-ui.card>

            {{-- Institutional approval --}}
            <x-ui.card title="Institutional approval" note="DRCP clearance, then the Director General and the Letter of Support">
                <div class="row" style="gap:0; margin-bottom:18px;">
                    @php
                        $chain = [
                            ['Internal review', $proposal->isReviewComplete() ? 'complete' : 'in progress', $proposal->isReviewComplete()],
                            ['DRCP', $drcp ? $drcp->status : 'pending', $drcp?->status === 'approved'],
                            ['Director General', $dg ? $dg->status : 'pending', $dg?->status === 'approved'],
                        ];
                    @endphp
                    @foreach($chain as [$label, $state, $done])
                        <div style="flex:1; text-align:center;">
                            <div class="tiny muted-3" style="text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px;">{{ $label }}</div>
                            <x-ui.pill :tone="$done ? 'ok' : ($state === 'returned' ? 'danger' : null)">{{ ucfirst($state) }}</x-ui.pill>
                        </div>
                    @endforeach
                </div>

                @foreach($proposal->approvals as $approval)
                    <div class="small" style="padding:10px 0; border-top:1px solid var(--line-soft);">
                        <strong>{{ $approval->levelName() }}</strong> — {{ ucfirst($approval->status) }}
                        by {{ $approval->decidedBy?->name ?? 'unknown' }} on {{ $approval->decided_at?->format('d M Y') }}
                        @if($approval->letter_of_support_issued)
                            <x-ui.pill tone="info">Letter of Support issued</x-ui.pill>
                        @endif
                        @if($approval->comments)<div class="muted" style="margin-top:4px;">{{ $approval->comments }}</div>@endif
                    </div>
                @endforeach

                @if(! $proposal->isReviewComplete())
                    <p class="small muted-3" style="margin-top:8px;">
                        All three internal reviews must be approved before the proposal goes for institutional sign-off.
                    </p>
                @elseif($proposal->isClearedForSubmission())
                    <div class="alert alert-success" style="margin:14px 0 0;">
                        <i class="fas fa-check-circle"></i><span>Cleared for submission to the donor.</span>
                    </div>
                @endif

                @if(($awaitingApproval === 'drcp' && $canApproveDrcp) || ($awaitingApproval === 'dg' && $canApproveDg))
                    <div class="divider"></div>
                    <form method="POST" action="{{ route('proposals.approval', $proposal) }}">
                        @csrf
                        <input type="hidden" name="level" value="{{ $awaitingApproval }}">
                        <div class="form-grid">
                            <x-ui.field :label="'Decision — ' . ProposalApproval::LEVELS[$awaitingApproval]" name="status">
                                <select name="status" class="select">
                                    <option value="approved">Approve</option>
                                    <option value="returned">Return to the team</option>
                                </select>
                            </x-ui.field>
                            @if($awaitingApproval === 'dg')
                                <x-ui.field label="Letter of Support">
                                    <label class="check" style="padding-top:9px;">
                                        <input type="checkbox" name="letter_of_support_issued" value="1"> Issued
                                    </label>
                                </x-ui.field>
                            @endif
                            <x-ui.field label="Comments" name="comments" wide>
                                <textarea name="comments" rows="2" class="textarea"></textarea>
                            </x-ui.field>
                        </div>
                        <div class="form-actions"><button class="btn btn-primary"><i class="fas fa-stamp"></i> Record decision</button></div>
                    </form>
                @endif
            </x-ui.card>

            {{-- Documents --}}
            <x-ui.card title="Proposal documents" note="Concept note, full proposal, budget, letters of support" :flush="true">
                <x-slot:action>
                    @if($canManage)
                        <button class="btn btn-sm" onclick="document.getElementById('add-doc').hidden = !document.getElementById('add-doc').hidden">
                            <i class="fas fa-paperclip"></i> Attach
                        </button>
                    @endif
                </x-slot:action>

                @if($canManage)
                    <form id="add-doc" hidden method="POST" action="{{ route('proposals.documents.store', $proposal) }}"
                          enctype="multipart/form-data" class="card-body" style="background:var(--canvas); border-bottom:1px solid var(--line-soft);">
                        @csrf
                        <div class="form-grid">
                            <x-ui.field label="Title" name="title" required><input name="title" class="input" required></x-ui.field>
                            <x-ui.field label="Type" name="document_type" required>
                                <select name="document_type" class="select" required>
                                    @foreach($documentTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </x-ui.field>
                            <x-ui.field label="File" name="file" required><input type="file" name="file" class="input" required></x-ui.field>
                        </div>
                        <div class="form-actions"><button class="btn btn-primary btn-sm">Attach document</button></div>
                    </form>
                @endif

                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Document</th><th>Type</th><th>Version</th><th>Uploaded by</th><th></th></tr></thead>
                        <tbody>
                            @forelse($proposal->documents->sortByDesc('created_at') as $doc)
                                <tr>
                                    <td>
                                        <a href="{{ route('proposals.documents.download', [$proposal, $doc]) }}" class="strong">{{ $doc->title }}</a>
                                        <div class="tiny muted-3">{{ $doc->file_name }} · {{ number_format(($doc->file_size ?? 0) / 1024, 0) }} KB</div>
                                    </td>
                                    <td><x-ui.pill>{{ $documentTypes[$doc->document_type] ?? $doc->document_type }}</x-ui.pill></td>
                                    <td class="mono small">v{{ $doc->version }}</td>
                                    <td class="small muted">{{ $doc->uploadedBy?->name }}</td>
                                    <td class="num">
                                        @if($canManage)
                                            <form method="POST" action="{{ route('proposals.documents.destroy', [$proposal, $doc]) }}"
                                                  onsubmit="return confirm('Remove this document?')" class="inline-form">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5">
                                    <x-ui.empty icon="fa-paperclip">
                                        Nothing attached. The concept note, budget and letters of support belong here.
                                    </x-ui.empty>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>

        <div class="stack">
            <x-ui.card title="Proposal details">
                <div class="stack-sm small">
                    <div class="row-between"><span class="muted">Lead PI</span><span>{{ $proposal->leadPi?->name }}</span></div>
                    <div class="row-between"><span class="muted">Assigned officer</span><span>{{ $proposal->assignedOfficer?->name ?? 'Unassigned' }}</span></div>
                    <div class="row-between"><span class="muted">Requested</span>
                        <span class="mono">{{ $proposal->requested_budget ? $proposal->currency . ' ' . number_format($proposal->requested_budget, 0) : '—' }}</span></div>
                    <div class="row-between"><span class="muted">Target submission</span><span>{{ $proposal->target_submission_date?->format('d M Y') ?? '—' }}</span></div>
                    <div class="row-between"><span class="muted">Submitted</span><span>{{ $proposal->submitted_at?->format('d M Y') ?? '—' }}</span></div>
                    <div class="row-between"><span class="muted">Outcome</span><span>{{ $proposal->outcome_date?->format('d M Y') ?? '—' }}</span></div>
                    <div class="row-between"><span class="muted">Origin</span><span>{{ Str::headline($proposal->initiation_type) }}</span></div>
                </div>
                @if($proposal->outcome_notes)
                    <div class="divider"></div>
                    <p class="small muted">{{ $proposal->outcome_notes }}</p>
                @endif
            </x-ui.card>

            <x-ui.card title="Research team" :flush="true">
                <x-slot:action>
                    @if($canManage && $availableMembers->isNotEmpty())
                        <button class="btn btn-sm" onclick="document.getElementById('add-member').hidden = !document.getElementById('add-member').hidden">
                            <i class="fas fa-user-plus"></i>
                        </button>
                    @endif
                </x-slot:action>

                @if($canManage && $availableMembers->isNotEmpty())
                    <form id="add-member" hidden method="POST" action="{{ route('proposals.team.store', $proposal) }}"
                          class="card-body" style="background:var(--canvas); border-bottom:1px solid var(--line-soft);">
                        @csrf
                        <x-ui.field label="Colleague" name="user_id">
                            <select name="user_id" class="select" required>
                                @foreach($availableMembers as $candidate)
                                    <option value="{{ $candidate->id }}">{{ $candidate->name }}@if($candidate->department) — {{ $candidate->department }}@endif</option>
                                @endforeach
                            </select>
                        </x-ui.field>
                        <x-ui.field label="Role" name="role">
                            <select name="role" class="select">
                                <option value="co_investigator">Co-investigator</option>
                                <option value="collaborator">Collaborator</option>
                            </select>
                        </x-ui.field>
                        <div class="form-actions"><button class="btn btn-primary btn-sm">Add to team</button></div>
                    </form>
                @endif

                <div class="list">
                    <div class="list-row">
                        <div class="list-main">
                            <span class="list-title">{{ $proposal->leadPi?->name }}</span>
                            <span class="list-sub">{{ $proposal->leadPi?->department }}</span>
                        </div>
                        <x-ui.pill tone="navy">Lead PI</x-ui.pill>
                    </div>
                    @foreach($proposal->teamMembers as $member)
                        <div class="list-row">
                            <div class="list-main">
                                <span class="list-title">{{ $member->user?->name }}</span>
                                <span class="list-sub">{{ $member->user?->department }}</span>
                            </div>
                            <x-ui.pill>{{ Str::headline($member->role) }}</x-ui.pill>
                            @if($canManage)
                                <form method="POST" action="{{ route('proposals.team.destroy', [$proposal, $member]) }}"
                                      onsubmit="return confirm('Remove this team member?')" class="inline-form">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger"><i class="fas fa-times"></i></button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card title="The call">
                <a href="{{ route('calls.show', $proposal->fundingCall) }}" class="strong">{{ $proposal->fundingCall?->title }}</a>
                <div class="stack-sm small" style="margin-top:12px;">
                    <div class="row-between"><span class="muted">Donor</span><span>{{ $proposal->fundingCall?->donor?->name }}</span></div>
                    <div class="row-between"><span class="muted">Deadline</span><span>{{ $proposal->fundingCall?->deadline?->format('d M Y') }}</span></div>
                </div>
            </x-ui.card>
        </div>
    </div>
</div>
@endsection
