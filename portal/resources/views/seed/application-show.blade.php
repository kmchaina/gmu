@extends('layouts.gmu')

@php
    use App\Support\SeedGrant as S;
    $stage = $application->stage();
    $s = $application->status;
    $round = $application->latestRound();
@endphp

@section('title', $application->reference)
@section('context', $application->reference)

@section('content')
<p class="crumbs"><a href="{{ route('dashboard') }}">Dashboard</a> / My application</p>

<div class="page-head">
    <div style="min-width:0; flex:1 1 520px;">
        <div class="row" style="gap:8px;"><span class="mono small muted">{{ $application->reference }}</span>@include('seed.partials.status')</div>
        <h1 style="margin-top:8px; font-size:clamp(22px,2.2vw,28px); line-height:1.25;">{{ $application->title ?: 'Untitled concept note' }}</h1>
    </div>
    <div class="page-actions">
        @can('update', $application)
            <a href="{{ route('seed.applications.edit', $application) }}" class="btn btn-primary">{{ $application->isRevising() ? 'Revise and respond' : ($s === S::SHORTLISTED ? 'Write full proposal' : 'Continue writing') }}</a>
        @endcan
        @if($application->agreement_issued_at)
            <a href="{{ route('seed.applications.agreement', $application) }}" class="btn {{ $s === S::AGREEMENT_ISSUED ? 'btn-primary' : '' }}">Grant agreement</a>
        @endif
    </div>
</div>

@php
    $banner = match ($s) {
        S::SUBMITTED          => ['info', 'fa-hourglass-half', 'Submitted and waiting for GMU screening', 'GMU checks eligibility, completeness and the budget range before reviewers see it.'],
        S::SCREENED           => ['info', 'fa-user-check', 'Passed screening', 'GMU is assigning reviewers.'],
        S::INELIGIBLE         => ['danger', 'fa-times', 'Not eligible', $application->screening_note],
        S::CONCEPT_REVIEW, S::PROPOSAL_REVIEW => ['info', 'fa-comments', 'With reviewers', 'Round ' . ($round?->number ?? 1) . ' is under review' . ($round?->due_at ? ', due ' . $round->due_at->format('j M Y') : '') . '. You will be told when GMU decides.'],
        S::CONCEPT_REVISION, S::PROPOSAL_REVISION => ['warn', 'fa-comment-dots', 'Reviewers asked you to revise', 'Read their comments below, revise, and send your response' . ($round?->response_due_at ? ' by ' . $round->response_due_at->format('j M Y') : '') . '.'],
        S::CONCEPT_DECLINED   => ['danger', 'fa-times', 'Not shortlisted', $round?->decision_note ?: 'Thank you for applying. The reviewers’ comments are below.'],
        S::SHORTLISTED        => ['ok', 'fa-star', 'You are shortlisted', 'Write your full proposal, detailed budget and upload the mentor letter and CV' . ($application->call->proposal_deadline ? ' by ' . $application->call->proposal_deadline->format('j M Y') : '') . '. Shortlisting does not guarantee an award.'],
        S::PROPOSAL_SUBMITTED => ['info', 'fa-hourglass-half', 'Full proposal submitted', 'GMU is assigning reviewers for the scored review.'],
        S::NOT_RECOMMENDED    => ['danger', 'fa-times', 'Not recommended for funding', $round?->decision_note ?: 'The reviewers’ comments are below.'],
        S::RECOMMENDED, S::WITH_DRCP, S::WITH_DG => ['ok', 'fa-award', 'Recommended for funding', 'Now going through institutional approval: GMU, the DRCP, then the Director General.'],
        S::APPROVED           => ['ok', 'fa-check', 'Approved', 'GMU will issue your grant agreement.'],
        S::AGREEMENT_ISSUED   => ['warn', 'fa-file-signature', 'Your grant agreement is ready', 'Read and accept it online.'],
        S::AGREEMENT_ACCEPTED => ['ok', 'fa-check', 'Agreement accepted', 'GMU is collecting the remaining signatures.'],
        S::AWARDED            => ['ok', 'fa-check-double', 'Awarded', 'The signed agreement is on file.'],
        default               => null,
    };
@endphp

@if($banner)
    <div class="banner banner-{{ $banner[0] }}" style="margin-bottom:24px;">
        <span class="ico"><i class="fas {{ $banner[1] }}" aria-hidden="true"></i></span>
        <div style="flex:1 1 360px;"><h2>{{ $banner[2] }}</h2>@if($banner[3])<p>{{ $banner[3] }}</p>@endif</div>
    </div>
@endif

<section class="card" style="margin-bottom:24px;">
    <div class="card-body">
        <dl class="form-grid" style="margin:0 0 20px; grid-template-columns:repeat(auto-fit,minmax(150px,1fr));">
            <div class="kv"><dt class="k">Requested</dt><dd class="v mono" style="margin:0;">{{ S::tzs($application->requestedAmount()) }}</dd></div>
            <div class="kv"><dt class="k">Concept submitted</dt><dd class="v" style="margin:0;">{{ $application->concept_submitted_at?->format('j M Y') ?? '—' }}</dd></div>
            <div class="kv"><dt class="k">Mentor</dt><dd class="v" style="margin:0;">{{ $application->mentor_name ?: '—' }}</dd></div>
            @if($application->approved_amount)
                <div class="kv"><dt class="k">Approved</dt><dd class="v mono" style="margin:0;">{{ S::tzs($application->approved_amount) }}</dd></div>
            @endif
        </dl>
        @include('seed.partials.track')
    </div>
</section>

<div class="grid grid-wide">
    <div class="stack" style="gap:16px;">
        <h2 style="font-size:18px;">{{ $stage === 'proposal' && $application->proposal ? 'Full proposal' : 'Concept note' }}</h2>
        @include('seed.partials.content', ['stage' => $stage === 'proposal' && $application->proposal ? 'proposal' : 'concept'])
    </div>
    <aside class="stack-sm" style="gap:16px;">
        <h2 style="font-size:18px;">Reviewer feedback</h2>
        @include('seed.partials.feedback')
    </aside>
</div>
@endsection
