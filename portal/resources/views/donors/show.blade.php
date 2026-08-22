@extends('adminlte::page')

@section('title', $donor->name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-building mr-2"></i>{{ $donor->name }}</h1>
        <div>
            <a href="{{ route('donors.edit', $donor) }}" class="btn btn-warning">
                <i class="fas fa-edit mr-1"></i>Edit
            </a>
            <a href="{{ route('donors.index') }}" class="btn btn-default ml-1">
                <i class="fas fa-arrow-left mr-1"></i>Back
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="row">

    {{-- Left: Donor Details --}}
    <div class="col-md-8">

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Donor Profile</h3>
                <div class="card-tools">
                    @php
                        $typeColors = [
                            'bilateral'    => 'primary',
                            'multilateral' => 'info',
                            'foundation'   => 'success',
                            'government'   => 'warning',
                            'private'      => 'secondary',
                            'other'        => 'light',
                        ];
                    @endphp
                    <span class="badge badge-{{ $typeColors[$donor->type] ?? 'secondary' }} badge-lg">
                        {{ ucfirst($donor->type) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    @if($donor->acronym)
                    <tr>
                        <th width="160" class="text-muted">Acronym</th>
                        <td><strong>{{ $donor->acronym }}</strong></td>
                    </tr>
                    @endif
                    @if($donor->country)
                    <tr>
                        <th class="text-muted">Country / Region</th>
                        <td>{{ $donor->country }}</td>
                    </tr>
                    @endif
                    @if($donor->website)
                    <tr>
                        <th class="text-muted">Website</th>
                        <td><a href="{{ $donor->website }}" target="_blank">{{ $donor->website }}</a></td>
                    </tr>
                    @endif
                    @if($donor->contact_email)
                    <tr>
                        <th class="text-muted">Contact Email</th>
                        <td><a href="mailto:{{ $donor->contact_email }}">{{ $donor->contact_email }}</a></td>
                    </tr>
                    @endif
                    <tr>
                        <th class="text-muted">Added</th>
                        <td>{{ $donor->created_at->format('d M Y') }}</td>
                    </tr>
                </table>

                @if($donor->notes)
                <hr>
                <h6 class="font-weight-bold">Notes</h6>
                <p class="text-muted">{{ $donor->notes }}</p>
                @endif
            </div>
        </div>

        {{-- Funding Calls --}}
        @if($donor->fundingCalls->count() > 0)
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bullhorn mr-1"></i>Funding Calls ({{ $donor->fundingCalls()->count() }})</h3>
                <div class="card-tools">
                    <a href="{{ route('calls.index', ['donor_id' => $donor->id]) }}" class="btn btn-xs btn-outline-info">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Title</th><th>Deadline</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach($donor->fundingCalls as $call)
                        <tr>
                            <td>{{ Str::limit($call->title, 55) }}</td>
                            <td>{{ $call->deadline->format('d M Y') }}</td>
                            <td>
                                @if($call->status === 'open') <span class="badge badge-success">Open</span>
                                @elseif($call->status === 'closed') <span class="badge badge-danger">Closed</span>
                                @elseif($call->status === 'draft') <span class="badge badge-warning">Draft</span>
                                @else <span class="badge badge-secondary">Archived</span>
                                @endif
                            </td>
                            <td><a href="{{ route('calls.show', $call) }}" class="btn btn-xs btn-outline-primary">View</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Awards --}}
        @if($donor->awards->count() > 0)
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-trophy mr-1"></i>Awards ({{ $donor->awards()->count() }})</h3>
                <div class="card-tools">
                    <a href="{{ route('awards.index', ['donor_id' => $donor->id]) }}" class="btn btn-xs btn-outline-success">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Project Title</th><th>PI</th><th>Amount</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach($donor->awards as $award)
                        <tr>
                            <td>{{ Str::limit($award->title, 45) }}</td>
                            <td>{{ $award->proposal->leadPi->name ?? '-' }}</td>
                            <td>{{ number_format($award->total_budget, 0) }} {{ $award->currency }}</td>
                            <td><a href="{{ route('awards.show', $award) }}" class="btn btn-xs btn-outline-success">View</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>

    {{-- Right: Stats --}}
    <div class="col-md-4">

        <div class="card">
            <div class="card-header"><h3 class="card-title">Quick Stats</h3></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Funding Calls</span>
                        <strong>{{ $donor->fundingCalls()->count() }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Open Calls</span>
                        <strong class="text-success">{{ $donor->fundingCalls()->where('status','open')->count() }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Active Awards</span>
                        <strong class="text-primary">{{ $donor->awards()->where('status','active')->count() }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Policies / Guidelines</span>
                        <strong>{{ $donor->policies()->count() }}</strong>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <a href="{{ route('calls.create') }}?donor_id={{ $donor->id }}" class="btn btn-outline-primary btn-block btn-sm">
                    <i class="fas fa-plus mr-1"></i>Post Call for This Donor
                </a>
                <a href="{{ route('donor-policies.index') }}?donor_id={{ $donor->id }}" class="btn btn-outline-secondary btn-block btn-sm mt-2">
                    <i class="fas fa-book mr-1"></i>View Donor Policies
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
