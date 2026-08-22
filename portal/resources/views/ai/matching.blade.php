@extends('adminlte::page')

@section('title', 'Grant Matching')

@section('content_header')
    <h1><i class="fas fa-users mr-2"></i>Grant Matching — Select a Call</h1>
@endsection

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title">Open Funding Calls</h3></div>
    <div class="card-body p-0">
        @forelse($calls as $call)
        <div class="d-flex align-items-center px-3 py-3 border-bottom">
            <div class="flex-grow-1">
                <strong>{{ $call->title }}</strong><br>
                <small class="text-muted">
                    {{ $call->donor?->acronym ?? 'Unknown Donor' }}
                    &middot; Deadline: {{ $call->deadline->format('d M Y') }}
                    @if($call->estimated_budget)
                        &middot; Budget: {{ number_format($call->estimated_budget) }} {{ $call->currency }}
                    @endif
                    @if($call->theme)
                        &middot; Theme: {{ $call->theme }}
                    @endif
                </small>
            </div>
            <a href="{{ route('ai.match-call', $call) }}" class="btn btn-sm btn-outline-primary ml-3">
                <i class="fas fa-search mr-1"></i>Match Researchers
            </a>
        </div>
        @empty
        <p class="text-center text-muted py-4">No open funding calls at this time.</p>
        @endforelse
    </div>
</div>
@endsection
