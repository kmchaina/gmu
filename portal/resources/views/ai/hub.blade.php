@extends('adminlte::page')

@section('title', 'AI Intelligence Hub')

@section('content_header')
    <h1><i class="fas fa-robot mr-2"></i>AI Intelligence Hub</h1>
@endsection

@section('content')

<div class="alert alert-{{ $llmActive ? 'success' : 'info' }}">
    <i class="fas fa-{{ $llmActive ? 'check-circle' : 'info-circle' }} mr-2"></i>
    <strong>AI Provider:</strong> {{ $providerName }}
    @if(!$llmActive)
        &mdash; Rule-based analysis is active. Set <code>ANTHROPIC_API_KEY</code> in <code>.env</code> to enable AI-generated narratives and proposal drafting.
    @endif
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card card-outline card-primary h-100">
            <div class="card-body text-center py-4">
                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                <h4>Grant Matching</h4>
                <p class="text-muted">Score and rank researchers against open funding calls based on expertise, track record, qualifications, and budget fit.</p>
                <a href="{{ route('ai.matching') }}" class="btn btn-primary">
                    <i class="fas fa-search mr-1"></i>Find Matches
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-outline card-danger h-100">
            <div class="card-body text-center py-4">
                <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                <h4>Compliance Risk Analysis</h4>
                <p class="text-muted">Scan active awards for overdue reports, expiring ethics protocols, budget over-runs, and upcoming compliance events.</p>
                <a href="{{ route('ai.risk') }}" class="btn btn-danger">
                    <i class="fas fa-shield-alt mr-1"></i>Analyse Risk
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-outline card-success h-100">
            <div class="card-body text-center py-4">
                <i class="fas fa-pen-fancy fa-3x text-success mb-3"></i>
                <h4>Proposal Drafting</h4>
                <p class="text-muted">
                    @if($llmActive)
                        AI-powered drafting of proposal sections: abstract, background, objectives, methodology, and impact.
                    @else
                        Requires AI provider. Configure <code>ANTHROPIC_API_KEY</code> to enable this feature.
                    @endif
                </p>
                @if($llmActive)
                    <a href="{{ route('proposals.create') }}" class="btn btn-success">
                        <i class="fas fa-file-alt mr-1"></i>New Proposal
                    </a>
                @else
                    <button class="btn btn-secondary" disabled>Not Available</button>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
