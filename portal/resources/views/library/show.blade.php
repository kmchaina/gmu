@extends('layouts.gmu')

@section('title', Str::limit($library->title, 40))
@section('crumb', 'Proposal library')

@section('content')
<div class="page-head">
    <div style="min-width:0;">
        <a href="{{ route('library.index') }}" class="small">&larr; Proposal library</a>
        <h1 style="margin-top:8px;">{{ $library->title }}</h1>
        <div class="row" style="gap:12px; margin-top:10px;">
            <x-ui.pill :tone="$library->is_approved ? 'ok' : 'warn'">
                {{ $library->is_approved ? 'Approved for use' : 'Awaiting approval' }}
            </x-ui.pill>
            <span class="small muted">{{ \App\Models\LibraryItem::categories()[$library->category] ?? $library->category }}</span>
            <span class="small muted-3">Used {{ $library->use_count }} {{ Str::plural('time', $library->use_count) }}</span>
        </div>
    </div>
    <div class="page-actions">
        <button class="btn" onclick="copyBlock()"><i class="fas fa-copy"></i> Copy text</button>
        @can('update', $library)
            <a href="{{ route('library.edit', $library) }}" class="btn"><i class="fas fa-pen"></i> Edit</a>
        @endcan
        @can('approve', $library)
            <form method="POST" action="{{ route('library.approve', $library) }}" class="inline-form">
                @csrf
                <button class="btn btn-primary">
                    <i class="fas {{ $library->is_approved ? 'fa-eye-slash' : 'fa-check' }}"></i>
                    {{ $library->is_approved ? 'Unpublish' : 'Approve' }}
                </button>
            </form>
        @endcan
    </div>
</div>

<div class="grid grid-wide">
    <x-ui.card title="The text">
        <div id="block-content" style="line-height:1.8; color:var(--ink-2); white-space:pre-line;">{{ $library->content }}</div>
    </x-ui.card>

    <div class="stack">
        @if($library->usage_notes)
            <x-ui.card title="When to use it">
                <p class="small muted" style="line-height:1.75;">{{ $library->usage_notes }}</p>
            </x-ui.card>
        @endif

        <x-ui.card title="Details">
            <div class="stack-sm small">
                <div class="row-between"><span class="muted">Category</span>
                    <span>{{ \App\Models\LibraryItem::categories()[$library->category] ?? $library->category }}</span></div>
                <div class="row-between"><span class="muted">Funders</span><span>{{ $library->funder_relevance ?? 'Any' }}</span></div>
                <div class="row-between"><span class="muted">Added by</span><span>{{ $library->createdBy?->name }}</span></div>
                <div class="row-between"><span class="muted">Last updated</span><span>{{ $library->updated_at?->format('d M Y') }}</span></div>
            </div>

            @if($library->tags)
                <div class="divider"></div>
                <div class="row" style="gap:6px;">
                    @foreach($library->tags as $tag)<x-ui.pill>{{ $tag }}</x-ui.pill>@endforeach
                </div>
            @endif
        </x-ui.card>
    </div>
</div>

@push('scripts')
<script>
function copyBlock() {
    const text = document.getElementById('block-content').innerText;
    navigator.clipboard.writeText(text).then(() => {
        fetch('{{ route('library.copy', $library) }}');
        const btn = event.currentTarget;
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied';
        setTimeout(() => btn.innerHTML = original, 2000);
    });
}
</script>
@endpush
@endsection
