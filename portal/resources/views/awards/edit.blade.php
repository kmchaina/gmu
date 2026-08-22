@extends('adminlte::page')

@section('title', 'Edit Award')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-edit mr-2"></i>Edit Award</h1>
        <a href="{{ route('awards.show', $award) }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
@endsection

@section('content')

<form action="{{ route('awards.update', $award) }}" method="POST" enctype="multipart/form-data">
@csrf
@method('PUT')

<div class="row">
    <div class="col-md-8">

        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title">Award Details</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label class="text-muted">Linked Proposal</label>
                    <p class="form-control-plaintext">
                        <a href="{{ route('proposals.show', $award->proposal) }}">{{ $award->proposal->title }}</a>
                        — {{ $award->proposal->leadPi->name ?? '-' }}
                    </p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Donor <span class="text-danger">*</span></label>
                            <select name="donor_id" class="form-control select2 @error('donor_id') is-invalid @enderror">
                                @foreach($donors as $donor)
                                    <option value="{{ $donor->id }}" {{ old('donor_id', $award->donor_id) == $donor->id ? 'selected' : '' }}>
                                        {{ $donor->name }} @if($donor->acronym)({{ $donor->acronym }})@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('donor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Contract / Grant Number <span class="text-danger">*</span></label>
                            <input type="text" name="contract_number" value="{{ old('contract_number', $award->contract_number) }}"
                                   class="form-control @error('contract_number') is-invalid @enderror">
                            @error('contract_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Project Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $award->title) }}"
                           class="form-control @error('title') is-invalid @enderror">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="3" class="form-control">{{ old('notes', $award->notes) }}</textarea>
                </div>

            </div>
        </div>

        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title">Budget & Duration</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Total Budget <span class="text-danger">*</span></label>
                            <input type="number" name="total_budget" value="{{ old('total_budget', $award->total_budget) }}"
                                   class="form-control @error('total_budget') is-invalid @enderror"
                                   step="0.01" min="0">
                            @error('total_budget')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Currency</label>
                            <select name="currency" class="form-control">
                                <option value="USD" {{ old('currency', $award->currency) === 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="EUR" {{ old('currency', $award->currency) === 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="GBP" {{ old('currency', $award->currency) === 'GBP' ? 'selected' : '' }}>GBP</option>
                                <option value="TZS" {{ old('currency', $award->currency) === 'TZS' ? 'selected' : '' }}>TZS</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date"
                                   value="{{ old('start_date', $award->start_date->format('Y-m-d')) }}"
                                   class="form-control @error('start_date') is-invalid @enderror">
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date"
                                   value="{{ old('end_date', $award->end_date->format('Y-m-d')) }}"
                                   class="form-control @error('end_date') is-invalid @enderror">
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Kickoff Date</label>
                            <input type="date" name="kickoff_date"
                                   value="{{ old('kickoff_date', $award->kickoff_date?->format('Y-m-d')) }}"
                                   class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="col-md-4">

        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Settings</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="active"     {{ old('status', $award->status) === 'active'     ? 'selected' : '' }}>Active</option>
                        <option value="suspended"  {{ old('status', $award->status) === 'suspended'  ? 'selected' : '' }}>Suspended</option>
                        <option value="completed"  {{ old('status', $award->status) === 'completed'  ? 'selected' : '' }}>Completed</option>
                        <option value="terminated" {{ old('status', $award->status) === 'terminated' ? 'selected' : '' }}>Terminated</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Replace Signed Contract</label>
                    @if($award->signed_contract)
                        <div class="mb-2">
                            <a href="{{ route('awards.contract', $award) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-file mr-1"></i>Current Contract
                            </a>
                        </div>
                    @endif
                    <div class="custom-file">
                        <input type="file" name="signed_contract" class="custom-file-input" id="contractFile"
                               accept=".pdf,.doc,.docx">
                        <label class="custom-file-label" for="contractFile">Choose file</label>
                    </div>
                </div>

            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save mr-1"></i> Save Changes
        </button>
        <a href="{{ route('awards.show', $award) }}" class="btn btn-default btn-block">Cancel</a>

    </div>
</div>

</form>

@endsection

@section('js')
<script>
document.querySelector('#contractFile')?.addEventListener('change', function(){
    this.nextElementSibling.textContent = this.files[0]?.name || 'Choose file';
});
</script>
@endsection
