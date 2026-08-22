@extends('adminlte::page')

@section('title', 'New Award')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-plus-circle mr-2"></i>Create Award</h1>
        <a href="{{ route('awards.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
@endsection

@section('content')

<form action="{{ route('awards.store') }}" method="POST" enctype="multipart/form-data">
@csrf

<div class="row">
    <div class="col-md-8">

        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title">Award Details</h3></div>
            <div class="card-body">

                <div class="form-group">
                    <label>Linked Proposal <span class="text-danger">*</span></label>
                    <select name="proposal_id" class="form-control select2 @error('proposal_id') is-invalid @enderror">
                        <option value="">-- Select Approved Proposal --</option>
                        @foreach($proposals as $proposal)
                            <option value="{{ $proposal->id }}"
                                {{ old('proposal_id', $selectedProposal?->id) == $proposal->id ? 'selected' : '' }}>
                                {{ $proposal->title }}
                                — {{ $proposal->leadPi?->name }}
                                ({{ $proposal->fundingCall->donor->acronym ?? $proposal->fundingCall->donor->name }})
                            </option>
                        @endforeach
                    </select>
                    @error('proposal_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Donor / Funder <span class="text-danger">*</span></label>
                            <select name="donor_id" class="form-control select2 @error('donor_id') is-invalid @enderror">
                                <option value="">-- Select Donor --</option>
                                @foreach($donors as $donor)
                                    <option value="{{ $donor->id }}"
                                        {{ old('donor_id', $selectedProposal?->fundingCall->donor_id) == $donor->id ? 'selected' : '' }}>
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
                            <input type="text" name="contract_number" value="{{ old('contract_number') }}"
                                   class="form-control @error('contract_number') is-invalid @enderror"
                                   placeholder="e.g. NIH-R01-2026-001">
                            @error('contract_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Project Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $selectedProposal?->title) }}"
                           class="form-control @error('title') is-invalid @enderror"
                           placeholder="Official title as per contract">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="3" class="form-control"
                              placeholder="Contract terms, special conditions, key contacts...">{{ old('notes') }}</textarea>
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
                            <input type="number" name="total_budget" value="{{ old('total_budget') }}"
                                   class="form-control @error('total_budget') is-invalid @enderror"
                                   placeholder="0.00" step="0.01" min="0">
                            @error('total_budget')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Currency</label>
                            <select name="currency" class="form-control">
                                <option value="USD" {{ old('currency','USD') === 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>GBP</option>
                                <option value="TZS" {{ old('currency') === 'TZS' ? 'selected' : '' }}>TZS</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}"
                                   class="form-control @error('start_date') is-invalid @enderror">
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" value="{{ old('end_date') }}"
                                   class="form-control @error('end_date') is-invalid @enderror">
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Kickoff Meeting Date</label>
                            <input type="date" name="kickoff_date" value="{{ old('kickoff_date') }}" class="form-control">
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
                        <option value="active"    {{ old('status','active') === 'active'    ? 'selected' : '' }}>Active</option>
                        <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="terminated"{{ old('status') === 'terminated'? 'selected' : '' }}>Terminated</option>
                    </select>
                </div>


                <div class="form-group">
                    <label>Reporting Frequency</label>
                    <select name="reporting_frequency" class="form-control">
                        @foreach(\App\Services\ComplianceScheduleGenerator::frequencies() as $value => $label)
                            <option value="{{ $value }}" {{ old('reporting_frequency', 'quarterly') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">
                        The reporting calendar is generated from this: a technical and a financial
                        report per period, each due 30 days after the period closes, plus the kickoff,
                        mid-term visit and closeout milestones.
                    </small>
                </div>

                <div class="form-group">
                    <label>Signed Contract</label>
                    <div class="custom-file">
                        <input type="file" name="signed_contract" class="custom-file-input" id="contractFile"
                               accept=".pdf,.doc,.docx">
                        <label class="custom-file-label" for="contractFile">Choose file (PDF/Word)</label>
                    </div>
                    <small class="text-muted">Max 10MB</small>
                </div>

            </div>
        </div>

        <div class="card bg-light">
            <div class="card-body">
                <p class="font-weight-bold small mb-2"><i class="fas fa-info-circle mr-1 text-primary"></i>After Creating:</p>
                <ul class="small text-muted pl-3 mb-0">
                    <li>Set up budget line items</li>
                    <li>Add compliance calendar events</li>
                    <li>Schedule monitoring visits</li>
                    <li>Track ethics/protocol renewals</li>
                </ul>
            </div>
        </div>

        <button type="submit" class="btn btn-success btn-block btn-lg">
            <i class="fas fa-trophy mr-1"></i> Create Award
        </button>
        <a href="{{ route('awards.index') }}" class="btn btn-default btn-block">Cancel</a>

    </div>
</div>

</form>

@endsection

@section('js')
<script>
document.querySelector('#contractFile').addEventListener('change', function(){
    var fileName = this.files[0]?.name || 'Choose file';
    this.nextElementSibling.textContent = fileName;
});
</script>
@endsection
