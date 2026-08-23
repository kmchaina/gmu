@extends('layouts.gmu')

@section('title', 'Proposal library')
@section('crumb', 'Proposals')

@section('content')
@php $current = request('category'); @endphp

<x-ui.page-head
    title="Proposal library"
    :lede="$items->total() . ' reusable ' . Str::plural('block', $items->total()) . ' · institutional text that does not need rewriting each time'">
    <x-slot:actions>
        <a href="{{ route('library.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add a block</a>
    </x-slot:actions>
</x-ui.page-head>

<div class="stack">

    <div class="row-between">
        <div class="chips">
            <a href="{{ route('library.index') }}" class="chip {{ ! $current ? 'is-active' : '' }}">All</a>
            @foreach($categories as $value => $label)
                <a href="{{ route('library.index', ['category' => $value]) }}" class="chip {{ $current === $value ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" class="row" style="gap:8px;">
            @if($current)<input type="hidden" name="category" value="{{ $current }}">@endif
            <input type="search" name="search" class="input" style="width:230px;" placeholder="Search the library" value="{{ request('search') }}">
            <button class="btn"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <div class="grid grid-cards">
        @forelse($items as $item)
            <div class="card">
                <div class="card-body" style="display:flex; flex-direction:column; gap:14px;">
                    <div class="row-between" style="align-items:flex-start;">
                        <div>
                            <div class="eyebrow">{{ $categories[$item->category] ?? $item->category }}</div>
                            <a href="{{ route('library.show', $item) }}"
                               style="display:block; margin-top:7px; font-size:14.5px; font-weight:600; line-height:1.35; color:var(--ink);">
                                {{ $item->title }}
                            </a>
                        </div>
                        @if($item->is_approved)
                            <x-ui.pill tone="ok">Approved</x-ui.pill>
                        @else
                            <x-ui.pill tone="warn">Draft</x-ui.pill>
                        @endif
                    </div>

                    <p class="small muted" style="line-height:1.65;">{{ Str::limit(strip_tags($item->content), 170) }}</p>

                    <div class="row-between" style="padding-top:12px; border-top:1px solid var(--line-soft);">
                        <span class="tiny muted-3">
                            Used {{ $item->use_count }} {{ Str::plural('time', $item->use_count) }}
                            @if($item->funder_relevance) · {{ $item->funder_relevance }}@endif
                        </span>
                        <a href="{{ route('library.show', $item) }}" class="btn btn-sm">Open</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="card" style="grid-column:1/-1;">
                <x-ui.empty icon="fa-book-open" title="The library is empty">
                    Capability statements, facilities descriptions, data management plans — the paragraphs every
                    proposal needs. Write them once, approve them, and reuse.
                    <x-slot:action>
                        <a href="{{ route('library.create') }}" class="btn btn-primary">Add the first block</a>
                    </x-slot:action>
                </x-ui.empty>
            </div>
        @endforelse
    </div>

    <div class="pagination-wrap">{{ $items->links() }}</div>
</div>
@endsection
