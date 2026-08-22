@extends('adminlte::page')

@section('title', 'Upload Document')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-upload mr-2"></i>Upload Document</h1>
        <a href="{{ route('documents.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
@endsection

@section('content')

<form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
@csrf

<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Document Details</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label>Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}"
                           class="form-control @error('title') is-invalid @enderror"
                           placeholder="e.g. Grant Application SOP v2">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-control @error('category') is-invalid @enderror">
                                <option value="">-- Select Category --</option>
                                <option value="sop"      {{ old('category') === 'sop'      ? 'selected' : '' }}>Standard Operating Procedure (SOP)</option>
                                <option value="template" {{ old('category') === 'template' ? 'selected' : '' }}>Template</option>
                                <option value="policy"   {{ old('category') === 'policy'   ? 'selected' : '' }}>Policy Document</option>
                                <option value="contract" {{ old('category') === 'contract' ? 'selected' : '' }}>Contract / Agreement</option>
                                <option value="report"   {{ old('category') === 'report'   ? 'selected' : '' }}>Report</option>
                                <option value="audit"    {{ old('category') === 'audit'    ? 'selected' : '' }}>Audit Document</option>
                                <option value="other"    {{ old('category') === 'other'    ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Version <span class="text-danger">*</span></label>
                            <input type="number" name="version" value="{{ old('version', 1) }}"
                                   class="form-control" min="1" max="99">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Access</label>
                            <div class="mt-2">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="is_public" value="1" class="custom-control-input"
                                           id="isPublic" {{ old('is_public') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="isPublic">Public</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" class="form-control"
                              placeholder="Brief description of this document...">{{ old('description') }}</textarea>
                </div>

                <div class="form-group">
                    <label>File <span class="text-danger">*</span></label>
                    <div class="custom-file">
                        <input type="file" name="file" class="custom-file-input @error('file') is-invalid @enderror"
                               id="docFile">
                        <label class="custom-file-label" for="docFile">Choose file (PDF/Word/Excel/etc.)</label>
                    </div>
                    @error('file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    <small class="text-muted">Max 20MB</small>
                </div>

            </div>
        </div>
    </div>

    <div class="col-md-4">
        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-upload mr-1"></i> Upload
        </button>
        <a href="{{ route('documents.index') }}" class="btn btn-default btn-block">Cancel</a>
    </div>
</div>

</form>

@endsection

@section('js')
<script>
document.querySelector('#docFile')?.addEventListener('change', function(){
    this.nextElementSibling.textContent = this.files[0]?.name || 'Choose file';
});
</script>
@endsection
