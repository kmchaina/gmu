@extends('adminlte::page')

@section('title', 'Proposal Library')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-book-open mr-2"></i>Proposal Library</h1>
        <a href="{{ route('library.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i>Add Content Block
        </a>
    </div>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
    </div>
@endif

{{-- Filters --}}
<div class="card card-outline card-primary">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('library.index') }}" class="row align-items-end g-2">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control"
                           value="{{ request('search') }}" placeholder="Search title or content…">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <select name="category" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="approved" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">All Items</option>
                    <option value="1" @selected(request('approved') === '1')>Approved Only</option>
                    <option value="0" @selected(request('approved') === '0')>Pending Approval</option>
                </select>
            </div>
            <div class="col-md-2">
                <a href="{{ route('library.index') }}" class="btn btn-sm btn-default w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Items grid --}}
<div class="row">
    @forelse($items as $item)
    <div class="col-md-6 col-lg-4">
        <div class="card card-outline {{ $item->is_approved ? 'card-success' : 'card-secondary' }} h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <a href="{{ route('library.show', $item) }}" class="text-dark">{{ $item->title }}</a>
                </h3>
                <div class="card-tools">
                    @if(!$item->is_approved && auth()->user()->hasRole('coordinator'))
                        <form method="POST" action="{{ route('library.approve', $item) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-xs btn-success" title="Approve">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                    @endif
                    @if($item->is_approved)
                        <span class="badge badge-success">Approved</span>
                    @else
                        <span class="badge badge-secondary">Pending</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <span class="badge badge-info mb-2">{{ $categories[$item->category] ?? $item->category }}</span>
                @if($item->funder_relevance)
                    <span class="badge badge-light mb-2">{{ $item->funder_relevance }}</span>
                @endif
                <p class="small text-muted mt-1 mb-0">{{ Str::limit(strip_tags($item->content), 150) }}</p>
                @if($item->tags)
                    <div class="mt-2">
                        @foreach($item->tags as $tag)
                            <span class="badge badge-secondary mr-1">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center py-2">
                <small class="text-muted">
                    <i class="fas fa-copy mr-1"></i>Used {{ $item->use_count }} times
                    &middot; {{ $item->updatedBy?->name ?? $item->createdBy?->name }}
                </small>
                <div>
                    <button class="btn btn-xs btn-outline-primary copy-btn"
                            data-id="{{ $item->id }}"
                            data-url="{{ route('library.copy', $item) }}"
                            title="Copy to clipboard">
                        <i class="fas fa-copy"></i>
                    </button>
                    <a href="{{ route('library.edit', $item) }}" class="btn btn-xs btn-outline-secondary">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-light text-center py-5">
            <i class="fas fa-book-open fa-2x text-muted mb-2"></i>
            <p class="mb-0 text-muted">No library items yet.
                <a href="{{ route('library.create') }}">Add the first content block.</a>
            </p>
        </div>
    </div>
    @endforelse
</div>

@if($items->hasPages())
    <div class="mt-3">{{ $items->links() }}</div>
@endif

@endsection

@section('js')
<script>
document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        fetch(this.dataset.url)
            .then(r => r.json())
            .then(data => {
                navigator.clipboard.writeText(data.content).then(() => {
                    const orig = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check text-success"></i>';
                    this.title = 'Copied!';
                    setTimeout(() => { this.innerHTML = orig; this.title = 'Copy to clipboard'; }, 2000);
                });
            });
    });
});
</script>
@endsection
