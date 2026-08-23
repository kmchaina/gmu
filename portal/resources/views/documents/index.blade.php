@extends('layouts.gmu')

@section('title', 'Document repository')
@section('crumb', 'Library')

@section('content')
@php $current = request('category'); @endphp

<x-ui.page-head
    title="Document repository"
    :lede="$documents->total() . ' ' . Str::plural('document', $documents->total()) . ' · SOPs, templates, contracts and audit evidence'">
    <x-slot:actions>
        <a href="{{ route('documents.create') }}" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</a>
    </x-slot:actions>
</x-ui.page-head>

<div class="stack">

    <div class="row-between">
        <div class="chips">
            <a href="{{ route('documents.index') }}" class="chip {{ ! $current ? 'is-active' : '' }}">All</a>
            @foreach(['sop' => 'SOPs', 'template' => 'Templates', 'policy' => 'Policies', 'contract' => 'Contracts', 'report' => 'Reports', 'audit' => 'Audit', 'other' => 'Other'] as $value => $label)
                <a href="{{ route('documents.index', ['category' => $value]) }}" class="chip {{ $current === $value ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" class="row" style="gap:8px;">
            @if($current)<input type="hidden" name="category" value="{{ $current }}">@endif
            <input type="search" name="search" class="input" style="width:220px;" placeholder="Search titles" value="{{ request('search') }}">
            <button class="btn"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <x-ui.card :flush="true">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Document</th><th>Category</th><th>Version</th><th>Uploaded</th><th>Visibility</th><th>Retain until</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                        <tr>
                            <td>
                                <a href="{{ route('documents.show', $doc) }}" class="strong">{{ $doc->title }}</a>
                                <div class="tiny muted-3">{{ $doc->file_name }}@if($doc->file_size) · {{ number_format($doc->file_size / 1024, 0) }} KB @endif</div>
                            </td>
                            <td><x-ui.pill>{{ Str::headline($doc->category) }}</x-ui.pill></td>
                            <td class="mono small">v{{ $doc->version }}</td>
                            <td class="small muted">
                                {{ $doc->created_at?->format('d M Y') }}
                                <div class="tiny muted-3">{{ $doc->uploadedBy?->name }}</div>
                            </td>
                            <td>
                                @if($doc->is_public)
                                    <x-ui.pill tone="info">Staff-wide</x-ui.pill>
                                @else
                                    <x-ui.pill>Restricted</x-ui.pill>
                                @endif
                            </td>
                            <td class="small {{ $doc->isDueForReview() ? 'text-warn' : 'muted' }}">
                                {{ $doc->retain_until?->format('M Y') ?? '—' }}
                                @if($doc->isDueForReview())<div class="tiny">due for review</div>@endif
                            </td>
                            <td class="num">
                                <a href="{{ route('documents.show', $doc) }}" class="btn btn-sm"><i class="fas fa-download"></i></a>
                                @can('update', $doc)
                                    <a href="{{ route('documents.edit', $doc) }}" class="btn btn-sm"><i class="fas fa-pen"></i></a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">
                            <x-ui.empty icon="fa-folder-open" title="Nothing filed yet">
                                SOPs, templates, signed contracts and audit responses belong here. Files are private by
                                default and served only to those allowed to see them.
                            </x-ui.empty>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div class="pagination-wrap">{{ $documents->links() }}</div>
</div>
@endsection
