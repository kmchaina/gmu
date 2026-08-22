@extends('adminlte::page')

@section('title', 'Document Repository')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-folder-open mr-2"></i>Document Repository</h1>
        <a href="{{ route('documents.create') }}" class="btn btn-primary">
            <i class="fas fa-upload mr-1"></i> Upload Document
        </a>
    </div>
@endsection

@section('content')

<div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-filter mr-1"></i>Filter</h3></div>
    <div class="card-body">
        <form method="GET" action="{{ route('documents.index') }}" class="form-inline flex-wrap" style="gap:10px;">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm" placeholder="Search title..." style="min-width:220px;">
            <select name="category" class="form-control form-control-sm">
                <option value="">All Categories</option>
                <option value="sop"      {{ request('category') === 'sop'      ? 'selected' : '' }}>SOP</option>
                <option value="template" {{ request('category') === 'template' ? 'selected' : '' }}>Template</option>
                <option value="policy"   {{ request('category') === 'policy'   ? 'selected' : '' }}>Policy</option>
                <option value="contract" {{ request('category') === 'contract' ? 'selected' : '' }}>Contract</option>
                <option value="report"   {{ request('category') === 'report'   ? 'selected' : '' }}>Report</option>
                <option value="audit"    {{ request('category') === 'audit'    ? 'selected' : '' }}>Audit</option>
                <option value="other"    {{ request('category') === 'other'    ? 'selected' : '' }}>Other</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search mr-1"></i>Search</button>
            <a href="{{ route('documents.index') }}" class="btn btn-sm btn-default">Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">{{ $documents->total() }} Document(s)</h3></div>
    <div class="card-body p-0">
        @if($documents->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fas fa-folder-open fa-3x mb-3"></i>
                <h5>No documents found.</h5>
            </div>
        @else
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Ver.</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                    <th>Access</th>
                    <th width="100">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($documents as $doc)
                <tr>
                    <td>
                        <a href="{{ Storage::url($doc->file_path) }}" target="_blank">
                            <i class="fas fa-file mr-1 text-muted"></i>{{ $doc->title }}
                        </a>
                        @if($doc->description)
                            <br><small class="text-muted">{{ Str::limit($doc->description, 60) }}</small>
                        @endif
                    </td>
                    <td><span class="badge badge-secondary">{{ ucfirst($doc->category) }}</span></td>
                    <td>v{{ $doc->version }}</td>
                    <td>{{ $doc->uploadedBy->name }}</td>
                    <td>{{ $doc->created_at->format('d M Y') }}</td>
                    <td>
                        @if($doc->is_public)
                            <span class="badge badge-success">Public</span>
                        @else
                            <span class="badge badge-secondary">Internal</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                           class="btn btn-xs btn-outline-primary" title="Download">
                            <i class="fas fa-download"></i>
                        </a>
                        @if(auth()->user()->hasAnyRole(['coordinator','planning_officer']) || $doc->uploaded_by === auth()->id())
                        <a href="{{ route('documents.edit', $doc) }}" class="btn btn-xs btn-outline-warning" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @if($documents->hasPages())
    <div class="card-footer">{{ $documents->links() }}</div>
    @endif
</div>

@endsection
