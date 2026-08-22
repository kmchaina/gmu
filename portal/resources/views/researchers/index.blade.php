@extends('adminlte::page')

@section('title', 'Researcher Profiles')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-user-graduate mr-2"></i>Researcher Profiles</h1>
    </div>
@endsection

@section('content')
<div class="row">
    @forelse($researchers as $user)
    <div class="col-xl-3 col-md-4 col-sm-6">
        <div class="card card-outline card-primary text-center">
            <div class="card-body pt-4 pb-3">
                <div class="mb-2">
                    <span class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                          style="width:56px;height:56px;font-size:1.4rem">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </span>
                </div>
                <h6 class="mb-0 font-weight-bold">
                    {{ $user->researcherProfile?->title ? $user->researcherProfile->title . ' ' : '' }}{{ $user->name }}
                </h6>
                <small class="text-muted">{{ $user->researcherProfile?->position ?? ucfirst(str_replace('_',' ',$user->role?->name ?? '')) }}</small>

                @if($user->researcherProfile?->expertise_areas)
                <div class="mt-2">
                    @foreach(array_slice($user->researcherProfile->expertise_areas, 0, 2) as $tag)
                        <span class="badge badge-info badge-sm">{{ $tag }}</span>
                    @endforeach
                    @if(count($user->researcherProfile->expertise_areas) > 2)
                        <span class="badge badge-secondary badge-sm">+{{ count($user->researcherProfile->expertise_areas) - 2 }}</span>
                    @endif
                </div>
                @endif

                <div class="row text-center mt-3 border-top pt-2">
                    <div class="col">
                        <div class="font-weight-bold text-primary small">{{ $user->researcherProfile?->total_grants_led ?? 0 }}</div>
                        <div style="font-size:10px" class="text-muted">Grants Led</div>
                    </div>
                    <div class="col border-left">
                        <div class="font-weight-bold text-success small">{{ number_format($user->researcherProfile?->total_funding_secured ?? 0) }}</div>
                        <div style="font-size:10px" class="text-muted">Funding</div>
                    </div>
                </div>
            </div>
            <div class="card-footer p-2">
                <a href="{{ route('researchers.show', $user) }}" class="btn btn-sm btn-outline-primary btn-block">
                    View Profile
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-info">No researcher profiles found. Researchers can complete their profiles after logging in.</div>
    </div>
    @endforelse
</div>

@if($researchers->hasPages())
<div class="d-flex justify-content-center">{{ $researchers->links() }}</div>
@endif
@endsection
