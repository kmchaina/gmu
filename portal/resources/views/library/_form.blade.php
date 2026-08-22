<form method="POST" action="{{ $route }}">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">Content Block Details</h3></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $item?->title) }}" placeholder="e.g. NIMR Institutional Capabilities Statement">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-control @error('category') is-invalid @enderror">
                            <option value="">— Select —</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" @selected(old('category', $item?->category) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Funder Relevance <small class="text-muted">comma-separated, e.g. NIH, USAID, Wellcome</small></label>
                        <input type="text" name="funder_relevance" class="form-control form-control-sm"
                               value="{{ old('funder_relevance', $item?->funder_relevance) }}"
                               placeholder="NIH, USAID, Wellcome, EU Horizon">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Tags <small class="text-muted">comma-separated</small></label>
                        <input type="text" name="tags" class="form-control form-control-sm"
                               value="{{ old('tags', implode(', ', $item?->tags ?? [])) }}"
                               placeholder="malaria, clinical, capacity">
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label>Content <span class="text-danger">*</span>
                            <small class="text-muted ml-2">The reusable text block researchers will copy into their proposals.</small>
                        </label>
                        <textarea name="content" rows="14"
                                  class="form-control @error('content') is-invalid @enderror"
                                  placeholder="Write the reusable content here…">{{ old('content', $item?->content) }}</textarea>
                        @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label>Usage Notes <small class="text-muted">When and how researchers should use this block.</small></label>
                        <textarea name="usage_notes" rows="3"
                                  class="form-control form-control-sm">{{ old('usage_notes', $item?->usage_notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i>{{ $item ? 'Update' : 'Save' }} Content Block
            </button>
            <a href="{{ route('library.index') }}" class="btn btn-secondary ml-2">Cancel</a>
            @if($item && !$item->is_approved && auth()->user()->hasRole('coordinator'))
                <form method="POST" action="{{ route('library.approve', $item) }}" class="d-inline ml-2">
                    @csrf
                    <button class="btn btn-success"><i class="fas fa-check mr-1"></i>Approve</button>
                </form>
            @endif
        </div>
    </div>
</form>
