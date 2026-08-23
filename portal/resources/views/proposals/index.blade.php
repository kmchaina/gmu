@extends('layouts.gmu')

@section('title', 'Proposals')
@section('crumb', 'Pipeline')

@section('content')
@php
    $stages = [
        'concept'           => ['Concept', 'info'],
        'under_development' => ['In development', 'info'],
        'internal_review'   => ['Internal review', 'warn'],
        'approved'          => ['Approved', 'ok'],
        'submitted'         => ['Submitted', 'navy'],
        'awarded'           => ['Awarded', 'ok'],
        'declined'          => ['Declined', 'danger'],
        'withdrawn'         => ['Withdrawn', null],
    ];
    $current = request('status');
@endphp

<x-ui.page-head
    title="Proposals"
    :lede="$proposals->total() . ' ' . Str::plural('proposal', $proposals->total()) . ' · concept through to donor decision'">
    <x-slot:actions>
        @can('create', App\Models\Proposal::class)
            <a href="{{ route('proposals.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Register a proposal</a>
        @endcan
    </x-slot:actions>
</x-ui.page-head>

<div class="stack">

    <div class="row-between">
        <div class="chips">
            <a href="{{ route('proposals.index') }}" class="chip {{ ! $current ? 'is-active' : '' }}">All</a>
            @foreach($stages as $value => [$label, $tone])
                <a href="{{ route('proposals.index', ['status' => $value]) }}"
                   class="chip {{ $current === $value ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>

        <form method="GET" class="row" style="gap:8px;">
            @if($current)<input type="hidden" name="status" value="{{ $current }}">@endif
            <input type="search" name="search" class="input" style="width:220px;" placeholder="Search proposal titles"
                   value="{{ request('search') }}">
            <button class="btn"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <x-ui.card :flush="true">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Proposal</th>
                        <th>Call &amp; donor</th>
                        <th>Lead PI</th>
                        <th>Stage</th>
                        <th class="num">Requested</th>
                        <th>Reviews</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($proposals as $proposal)
                        @php [$label, $tone] = $stages[$proposal->status] ?? [Str::headline($proposal->status), null]; @endphp
                        <tr>
                            <td>
                                <a href="{{ route('proposals.show', $proposal) }}" class="strong">{{ Str::limit($proposal->title, 52) }}</a>
                                <div class="tiny muted-3" style="margin-top:3px;">
                                    Registered {{ $proposal->created_at?->format('d M Y') }}
                                    @if($proposal->target_submission_date) · target {{ $proposal->target_submission_date->format('d M Y') }}@endif
                                </div>
                            </td>
                            <td>
                                <div>{{ Str::limit($proposal->fundingCall?->title, 34) }}</div>
                                <div class="tiny muted-3">{{ $proposal->fundingCall?->donor?->acronym ?? $proposal->fundingCall?->donor?->name }}</div>
                            </td>
                            <td>{{ $proposal->leadPi?->name }}</td>
                            <td><x-ui.pill :tone="$tone">{{ $label }}</x-ui.pill></td>
                            <td class="num">
                                {{ $proposal->requested_budget ? $proposal->currency . ' ' . number_format($proposal->requested_budget, 0) : '—' }}
                            </td>
                            <td>
                                @php $done = $proposal->approved_reviews_count; @endphp
                                <span class="mono small">{{ $done }}/3</span>
                                <div class="meter" style="width:52px; margin-top:5px;"><span style="width: {{ ($done / 3) * 100 }}%"></span></div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <x-ui.empty icon="fa-file-alt" title="No proposals yet">
                                Proposals appear here from the moment a concept is registered against a call, and stay
                                until the donor decides.
                            </x-ui.empty>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div class="pagination-wrap">{{ $proposals->links() }}</div>
</div>
@endsection
