@extends('adminlte::page')

@section('title', 'Matching Results')

@section('content_header')
    <h1><i class="fas fa-users mr-2"></i>Researcher Matches</h1>
@endsection

@section('content')
<div class="card card-outline card-info mb-3">
    <div class="card-body py-2">
        <strong>Call:</strong> {{ $call->title }} &nbsp;|&nbsp;
        <strong>Donor:</strong> {{ $call->donor?->name }} &nbsp;|&nbsp;
        <strong>Deadline:</strong> {{ $call->deadline->format('d M Y') }}
    </div>
</div>

@forelse($matches as $i => $match)
<div class="card card-outline {{ $match['grade'] === 'Excellent' ? 'card-success' : ($match['grade'] === 'Good' ? 'card-info' : ($match['grade'] === 'Fair' ? 'card-warning' : 'card-secondary')) }}">
    <div class="card-header">
        <h3 class="card-title">
            #{{ $i + 1 }} &nbsp; {{ $match['researcher'] }}
        </h3>
        <div class="card-tools d-flex align-items-center">
            <div class="mr-3">
                <div class="progress" style="width:100px;height:10px">
                    <div class="progress-bar bg-{{ $match['grade'] === 'Excellent' ? 'success' : ($match['grade'] === 'Good' ? 'info' : ($match['grade'] === 'Fair' ? 'warning' : 'secondary')) }}"
                         style="width:{{ $match['score'] }}%"></div>
                </div>
            </div>
            <span class="font-weight-bold">{{ $match['score'] }}/100</span>
            <span class="badge badge-{{ $match['grade'] === 'Excellent' ? 'success' : ($match['grade'] === 'Good' ? 'info' : ($match['grade'] === 'Fair' ? 'warning' : 'secondary')) }} ml-2">
                {{ $match['grade'] }}
            </span>
        </div>
    </div>
    <div class="card-body">
        @if(!empty($match['ai_summary']))
            <div class="alert alert-light py-2 mb-3">
                <i class="fas fa-robot mr-1 text-muted"></i>
                <em>{{ $match['ai_summary'] }}</em>
            </div>
        @endif
        <div class="row">
            @foreach($match['reasons'] as $reason)
            <div class="col-md-4 mb-2">
                <div class="d-flex align-items-start">
                    <span class="badge badge-success mr-2 mt-1">+{{ $reason['points'] }}</span>
                    <div>
                        <strong class="small">{{ $reason['factor'] }}</strong><br>
                        <small class="text-muted">{{ $reason['detail'] }}</small>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@empty
<div class="alert alert-warning">
    No active researchers found with profiles. Ensure researchers have completed their profiles under Researcher Profiles.
</div>
@endforelse

<a href="{{ route('ai.matching') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left mr-1"></i>Back
</a>
@endsection
