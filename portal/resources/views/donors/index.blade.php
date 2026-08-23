@extends('layouts.gmu')

@section('title', 'Donors & funders')
@section('crumb', 'Grant opportunities')

@section('content')
<x-ui.page-head
    title="Donors &amp; funders"
    :lede="$donors->total() . ' ' . Str::plural('funder', $donors->total()) . ' on record · calls, awards and the guidelines that go with them'">
    <x-slot:actions>
        @can('create', App\Models\Donor::class)
            <a href="{{ route('donors.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add a donor</a>
        @endcan
    </x-slot:actions>
</x-ui.page-head>

<div class="stack">
    <div class="row-between">
        <div class="chips">
            <a href="{{ route('donors.index') }}" class="chip {{ ! request('type') ? 'is-active' : '' }}">All</a>
            @foreach(['bilateral' => 'Bilateral', 'multilateral' => 'Multilateral', 'foundation' => 'Foundation', 'government' => 'Government', 'private' => 'Private', 'other' => 'Other'] as $value => $label)
                <a href="{{ route('donors.index', ['type' => $value]) }}" class="chip {{ request('type') === $value ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" class="row" style="gap:8px;">
            <input type="search" name="search" class="input" style="width:220px;" placeholder="Name, acronym or country" value="{{ request('search') }}">
            <button class="btn"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <div class="grid grid-cards">
        @forelse($donors as $donor)
            <div class="card">
                <div class="card-body" style="display:flex; flex-direction:column; gap:16px;">
                    <div class="row-between" style="align-items:flex-start;">
                        <div style="min-width:0;">
                            <div class="eyebrow">{{ Str::headline($donor->type) }}@if($donor->country) · {{ $donor->country }}@endif</div>
                            <a href="{{ route('donors.show', $donor) }}"
                               style="display:block; margin-top:7px; font-size:15px; font-weight:600; letter-spacing:-.2px; line-height:1.35; color:var(--ink);">
                                {{ $donor->name }}
                            </a>
                            @if($donor->acronym)<div class="tiny mono muted-3" style="margin-top:5px;">{{ $donor->acronym }}</div>@endif
                        </div>
                        @can('update', $donor)
                            <a href="{{ route('donors.edit', $donor) }}" class="btn btn-sm"><i class="fas fa-pen"></i></a>
                        @endcan
                    </div>

                    <div class="row-between" style="padding-top:14px; border-top:1px solid var(--line-soft);">
                        <div class="kv"><span class="k">Calls</span><span class="v">{{ $donor->funding_calls_count }}</span></div>
                        <div class="kv"><span class="k">Awards</span><span class="v">{{ $donor->awards_count }}</span></div>
                        <div class="kv"><span class="k">Guidelines</span><span class="v">{{ $donor->policies_count }}</span></div>
                        @if($donor->contact_email)
                            <div class="kv"><span class="k">Contact</span><span class="v small">{{ Str::limit($donor->contact_email, 18) }}</span></div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="card" style="grid-column:1/-1;">
                <x-ui.empty icon="fa-handshake" title="No donors recorded">
                    Every call and every award is attached to a funder. Add them here first.
                    <x-slot:action>
                        <a href="{{ route('donors.create') }}" class="btn btn-primary">Add the first donor</a>
                    </x-slot:action>
                </x-ui.empty>
            </div>
        @endforelse
    </div>

    <div class="pagination-wrap">{{ $donors->links() }}</div>
</div>
@endsection
