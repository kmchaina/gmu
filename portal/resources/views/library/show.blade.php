@extends('adminlte::page')

@section('title', $library->title)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-book-open mr-2"></i>{{ $library->title }}</h1>
        <div>
            <button class="btn btn-sm btn-outline-primary" id="copyBtn"
                    data-url="{{ route('library.copy', $library) }}">
                <i class="fas fa-copy mr-1"></i>Copy Text
            </button>
            <a href="{{ route('library.edit', $library) }}" class="btn btn-sm btn-secondary ml-1">
                <i class="fas fa-edit mr-1"></i>Edit
            </a>
        </div>
    </div>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
    </div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Content</h3>
                <div class="card-tools">
                    @if($library->is_approved)
                        <span class="badge badge-success">Approved</span>
                    @else
                        <span class="badge badge-warning">Pending Approval</span>
                        @if(auth()->user()->hasRole('coordinator'))
                            <form method="POST" action="{{ route('library.approve', $library) }}" class="d-inline ml-1">
                                @csrf
                                <button class="btn btn-xs btn-success">Approve Now</button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
            <div class="card-body">
                <pre id="contentBlock" class="bg-light p-3 rounded"
                     style="white-space:pre-wrap;font-family:inherit;font-size:.9rem;line-height:1.6">{{ $library->content }}</pre>
            </div>
        </div>

        @if($library->usage_notes)
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title">Usage Notes</h3></div>
            <div class="card-body">{{ $library->usage_notes }}</div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Details</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr>
                        <th class="pl-3 w-40">Category</th>
                        <td>{{ \App\Models\LibraryItem::categories()[$library->category] ?? $library->category }}</td>
                    </tr>
                    @if($library->funder_relevance)
                    <tr>
                        <th class="pl-3">Funders</th>
                        <td>{{ $library->funder_relevance }}</td>
                    </tr>
                    @endif
                    @if($library->tags)
                    <tr>
                        <th class="pl-3">Tags</th>
                        <td>
                            @foreach($library->tags as $tag)
                                <span class="badge badge-secondary mr-1">{{ $tag }}</span>
                            @endforeach
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <th class="pl-3">Times Used</th>
                        <td><strong>{{ $library->use_count }}</strong></td>
                    </tr>
                    <tr>
                        <th class="pl-3">Created By</th>
                        <td>{{ $library->createdBy?->name }}</td>
                    </tr>
                    <tr>
                        <th class="pl-3">Last Updated</th>
                        <td>{{ $library->updated_at->format('d M Y') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if(auth()->user()->hasAnyRole(['coordinator', 'planning_officer']))
        <form method="POST" action="{{ route('library.destroy', $library) }}">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger w-100"
                    onclick="return confirm('Remove this library item?')">
                <i class="fas fa-trash mr-1"></i>Remove from Library
            </button>
        </form>
        @endif
    </div>
</div>

<a href="{{ route('library.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left mr-1"></i>Back to Library
</a>
@endsection

@section('js')
<script>
document.getElementById('copyBtn').addEventListener('click', function () {
    fetch(this.dataset.url)
        .then(r => r.json())
        .then(data => {
            navigator.clipboard.writeText(data.content).then(() => {
                this.innerHTML = '<i class="fas fa-check mr-1"></i>Copied!';
                this.classList.replace('btn-outline-primary', 'btn-success');
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-copy mr-1"></i>Copy Text';
                    this.classList.replace('btn-success', 'btn-outline-primary');
                }, 2500);
            });
        });
});
</script>
@endsection
