@extends('adminlte::page')

@section('title', 'Add Report')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-plus-circle mr-2"></i>Add Report</h1>
        <a href="{{ route('reports.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
@endsection

@section('content')

<form action="{{ route('reports.store') }}" method="POST" enctype="multipart/form-data">
@csrf

<div class="row">
    <div class="col-md-8">

        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title">Report Details</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label>Award <span class="text-danger">*</span></label>
                    <select name="award_id" class="form-control select2 @error('award_id') is-invalid @enderror">
                        <option value="">-- Select Award --</option>
                        @foreach($awards as $award)
                            <option value="{{ $award->id }}"
                                {{ old('award_id', $selectedAward?->id) == $award->id ? 'selected' : '' }}>
                                {{ $award->title }} — {{ $award->donor->acronym ?? $award->donor->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('award_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Report Type <span class="text-danger">*</span></label>
                            <select name="report_type" class="form-control @error('report_type') is-invalid @enderror">
                                <option value="technical" {{ old('report_type') === 'technical' ? 'selected' : '' }}>Technical</option>
                                <option value="financial" {{ old('report_type') === 'financial' ? 'selected' : '' }}>Financial</option>
                                <option value="combined"  {{ old('report_type') === 'combined'  ? 'selected' : '' }}>Combined (Technical + Financial)</option>
                            </select>
                            @error('report_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control">
                                <option value="pending"     {{ old('status','pending')     === 'pending'     ? 'selected' : '' }}>Pending (not yet due)</option>
                                <option value="submitted"   {{ old('status') === 'submitted'   ? 'selected' : '' }}>Submitted</option>
                                <option value="under_review"{{ old('status') === 'under_review'? 'selected' : '' }}>Under Review</option>
                                <option value="approved"    {{ old('status') === 'approved'    ? 'selected' : '' }}>Approved</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Report Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}"
                           class="form-control @error('title') is-invalid @enderror"
                           placeholder="e.g. Year 1 Technical Progress Report">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Reporting Period Start</label>
                            <input type="date" name="period_start" value="{{ old('period_start') }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Reporting Period End</label>
                            <input type="date" name="period_end" value="{{ old('period_end') }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" value="{{ old('due_date') }}"
                                   class="form-control @error('due_date') is-invalid @enderror">
                            @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>GMU Comments / Notes</label>
                    <textarea name="gmu_comments" rows="3" class="form-control"
                              placeholder="Internal notes on this report...">{{ old('gmu_comments') }}</textarea>
                </div>

                <div class="form-group">
                    <label>Report File (PDF/Word/Excel)</label>
                    <div class="custom-file">
                        <input type="file" name="file_path" class="custom-file-input" id="reportFile"
                               accept=".pdf,.doc,.docx,.xlsx">
                        <label class="custom-file-label" for="reportFile">Choose file</label>
                    </div>
                    <small class="text-muted">Max 20MB</small>
                </div>

            </div>
        </div>

    </div>

    <div class="col-md-4">

        <div class="card bg-light">
            <div class="card-body">
                <p class="font-weight-bold mb-2 small"><i class="fas fa-info-circle mr-1 text-primary"></i>Report Types</p>
                <dl class="small text-muted">
                    <dt>Technical</dt><dd>Progress narrative, milestones, activities</dd>
                    <dt>Financial</dt><dd>Budget expenditure, financial statements</dd>
                    <dt>Combined</dt><dd>Both technical and financial in one report</dd>
                </dl>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save mr-1"></i> Save Report
        </button>
        <a href="{{ route('reports.index') }}" class="btn btn-default btn-block">Cancel</a>

    </div>
</div>

</form>

@endsection

@section('js')
<script>
document.querySelector('#reportFile')?.addEventListener('change', function(){
    this.nextElementSibling.textContent = this.files[0]?.name || 'Choose file';
});
</script>
@endsection
