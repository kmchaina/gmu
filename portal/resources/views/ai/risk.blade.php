@extends('adminlte::page')

@section('title', 'Compliance Risk Analysis')

@section('content_header')
    <h1><i class="fas fa-shield-alt mr-2"></i>Compliance Risk Analysis — Select an Award</h1>
@endsection

@section('content')
<div class="card card-outline card-danger">
    <div class="card-header"><h3 class="card-title">Active Awards</h3></div>
    <div class="card-body p-0">
        @forelse($awards as $award)
        <div class="d-flex align-items-center px-3 py-3 border-bottom">
            <div class="flex-grow-1">
                <strong>{{ $award->title }}</strong><br>
                <small class="text-muted">
                    {{ $award->contract_number }}
                    &middot; {{ $award->donor?->acronym ?? 'N/A' }}
                    &middot; Ends: {{ $award->end_date?->format('d M Y') ?? '—' }}
                </small>
            </div>
            <a href="{{ route('ai.risk-analysis', $award) }}" class="btn btn-sm btn-outline-danger ml-3">
                <i class="fas fa-search mr-1"></i>Analyse
            </a>
        </div>
        @empty
        <p class="text-center text-muted py-4">No active awards found.</p>
        @endforelse
    </div>
</div>
@endsection
