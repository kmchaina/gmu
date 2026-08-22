@extends('adminlte::page')

@section('title', 'Edit Document')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-edit mr-2"></i>Edit Document</h1>
        <a href="{{ route('documents.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
@endsection

@section('content')

<form action="{{ route('documents.update', $document) }}" method="POST">
@csrf
@method('PUT')

<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Document Details</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label>Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $document->title) }}"
                           class="form-control @error('title') is-invalid @enderror">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" class="form-control">
                                <option value="sop"      {{ old('category', $document->category) === 'sop'      ? 'selected' : '' }}>SOP</option>
                                <option value="template" {{ old('category', $document->category) === 'template' ? 'selected' : '' }}>Template</option>
                                <option value="policy"   {{ old('category', $document->category) === 'policy'   ? 'selected' : '' }}>Policy</option>
                                <option value="contract" {{ old('category', $document->category) === 'contract' ? 'selected' : '' }}>Contract</option>
                                <option value="report"   {{ old('category', $document->category) === 'report'   ? 'selected' : '' }}>Report</option>
                                <option value="audit"    {{ old('category', $document->category) === 'audit'    ? 'selected' : '' }}>Audit</option>
                                <option value="other"    {{ old('category', $document->category) === 'other'    ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Version</label>
                            <input type="number" name="version" value="{{ old('version', $document->version) }}"
                                   class="form-control" min="1" max="99">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Access</label>
                            <div class="mt-2">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="is_public" value="1" class="custom-control-input"
                                           id="isPublic" {{ old('is_public', $document->is_public) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="isPublic">Public</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" class="form-control">{{ old('description', $document->description) }}</textarea>
                </div>

                <div class="form-group">
                    <label>Current File</label>
                    <p>
                        <a href="{{ Storage::url($document->file_path) }}" target="_blank" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-download mr-1"></i>{{ $document->file_name }} (v{{ $document->version }})
                        </a>
                    </p>
                    <small class="text-muted">To replace the file, upload a new version.</small>
                </div>

            </div>
        </div>
    </div>

    <div class="col-md-4">
        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save mr-1"></i> Save Changes
        </button>
        <a href="{{ route('documents.index') }}" class="btn btn-default btn-block">Cancel</a>

        <hr>
        <form action="{{ route('documents.destroy', $document) }}" method="POST"
              onsubmit="return confirm('Delete this document?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-block btn-sm">
                <i class="fas fa-trash mr-1"></i>Delete Document
            </button>
        </form>
    </div>
</div>

</form>

@endsection
