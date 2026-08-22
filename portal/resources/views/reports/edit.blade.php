@extends('adminlte::page')

@section('title', 'Edit Report')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-edit mr-2"></i>Edit Report</h1>
        <a href="{{ route('reports.show', $report) }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
@endsection

@section('content')

<form action="{{ route('reports.update', $report) }}" method="POST" enctype="multipart/form-data">
@csrf
@method('PUT')

<div class="row">
    <div class="col-md-8">

        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title">Report Details</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label class="text-muted">Award</label>
                    <p class="form-control-plaintext">
                        <a href="{{ route('awards.show', $report->award) }}">{{ $report->award->title }}</a>
                        — {{ $report->award->donor->name }}
                    </p>
                    <input type="hidden" name="award_id" value="{{ $report->award_id }}">
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Report Type</label>
                            <select name="report_type" class="form-control">
                                <option value="technical" {{ old('report_type', $report->report_type) === 'technical' ? 'selected' : '' }}>Technical</option>
                                <option value="financial" {{ old('report_type', $report->report_type) === 'financial' ? 'selected' : '' }}>Financial</option>
                                <option value="combined"  {{ old('report_type', $report->report_type) === 'combined'  ? 'selected' : '' }}>Combined</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="pending"     {{ old('status', $report->status) === 'pending'     ? 'selected' : '' }}>Pending</option>
                                <option value="submitted"   {{ old('status', $report->status) === 'submitted'   ? 'selected' : '' }}>Submitted</option>
                                <option value="under_review"{{ old('status', $report->status) === 'under_review'? 'selected' : '' }}>Under Review</option>
                                <option value="approved"    {{ old('status', $report->status) === 'approved'    ? 'selected' : '' }}>Approved</option>
                                <option value="rejected"    {{ old('status', $report->status) === 'rejected'    ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Report Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $report->title) }}"
                           class="form-control @error('title') is-invalid @enderror">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Period Start</label>
                            <input type="date" name="period_start"
                                   value="{{ old('period_start', $report->period_start?->format('Y-m-d')) }}"
                                   class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Period End</label>
                            <input type="date" name="period_end"
                                   value="{{ old('period_end', $report->period_end?->format('Y-m-d')) }}"
                                   class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="due_date"
                                   value="{{ old('due_date', $report->due_date->format('Y-m-d')) }}"
                                   class="form-control @error('due_date') is-invalid @enderror">
                            @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>GMU Comments</label>
                    <textarea name="gmu_comments" rows="3" class="form-control">{{ old('gmu_comments', $report->gmu_comments) }}</textarea>
                </div>

                <div class="form-group">
                    <label>Replace Report File</label>
                    @if($report->file_path)
                        <div class="mb-2">
                            <a href="{{ route('reports.download', $report) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-download mr-1"></i>Current File
                            </a>
                        </div>
                    @endif
                    <div class="custom-file">
                        <input type="file" name="file_path" class="custom-file-input" id="reportFile"
                               accept=".pdf,.doc,.docx,.xlsx">
                        <label class="custom-file-label" for="reportFile">Choose file</label>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <div class="col-md-4">

        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save mr-1"></i> Save Changes
        </button>
        <a href="{{ route('reports.show', $report) }}" class="btn btn-default btn-block">Cancel</a>

        <hr>
        <form action="{{ route('reports.destroy', $report) }}" method="POST"
              onsubmit="return confirm('Delete this report?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-block btn-sm">
                <i class="fas fa-trash mr-1"></i>Delete Report
            </button>
        </form>

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
