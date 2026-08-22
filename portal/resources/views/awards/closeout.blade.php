@extends('adminlte::page')

@section('title', 'Closeout — ' . $award->contract_number)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-flag-checkered mr-2"></i>Closeout &amp; Lessons Learned
            <small class="text-muted d-block" style="font-size:14px">{{ $award->contract_number }} — {{ $award->title }}</small>
        </h1>
        <a href="{{ route('awards.show', $award) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i>Back to award
        </a>
    </div>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">

        <div class="card card-outline card-success">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Closeout checklist</h3>
                <span class="badge badge-{{ $closeout->status === 'completed' ? 'success' : ($closeout->progress() > 0 ? 'warning' : 'secondary') }}">
                    {{ ucfirst(str_replace('_', ' ', $closeout->status)) }} — {{ $closeout->progress() }}%
                </span>
            </div>

            <div class="card-body">
                <div class="progress mb-4" style="height:8px">
                    <div class="progress-bar bg-success" role="progressbar"
                         style="width: {{ $closeout->progress() }}%"
                         aria-valuenow="{{ $closeout->progress() }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <form method="POST" action="{{ route('awards.closeout.update', $award) }}">
                    @csrf @method('PATCH')

                    @foreach(\App\Models\AwardCloseout::STEPS as $column => $label)
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="step_{{ $column }}"
                                   name="steps[]" value="{{ $column }}"
                                   {{ $closeout->{$column} ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>
                            <label class="custom-control-label" for="step_{{ $column }}">
                                {{ $label }}
                                @if($closeout->{$column})
                                    <small class="text-muted">— {{ $closeout->{$column}->format('j M Y') }}</small>
                                @endif
                            </label>
                        </div>
                    @endforeach

                    <hr>
                    <h5 class="mb-3">Final reconciliation</h5>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="small text-uppercase text-muted">Total expenditure ({{ $award->currency }})</label>
                            <input type="number" step="0.01" min="0" name="total_expenditure" class="form-control"
                                   value="{{ old('total_expenditure', $closeout->total_expenditure ?? $readiness['spent']) }}" {{ $canEdit ? '' : 'readonly' }}>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="small text-uppercase text-muted">Unspent balance</label>
                            <input type="number" step="0.01" name="unspent_balance" class="form-control"
                                   value="{{ old('unspent_balance', $closeout->unspent_balance ?? $readiness['balance']) }}" {{ $canEdit ? '' : 'readonly' }}>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="small text-uppercase text-muted">Returned to donor</label>
                            <input type="number" step="0.01" min="0" name="funds_returned" class="form-control"
                                   value="{{ old('funds_returned', $closeout->funds_returned) }}" {{ $canEdit ? '' : 'readonly' }}>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="small text-uppercase text-muted">Date funds returned</label>
                            <input type="date" name="funds_returned_on" class="form-control"
                                   value="{{ old('funds_returned_on', $closeout->funds_returned_on?->toDateString()) }}" {{ $canEdit ? '' : 'readonly' }}>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="small text-uppercase text-muted">Archive reference</label>
                            <input type="text" name="archive_reference" class="form-control"
                                   placeholder="Where the project file now lives"
                                   value="{{ old('archive_reference', $closeout->archive_reference) }}" {{ $canEdit ? '' : 'readonly' }}>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-1">Lessons learned</h5>
                    <p class="text-muted small">
                        The point of closeout is what the unit carries into the next proposal. Write for
                        a colleague who was not on this grant.
                    </p>

                    <div class="form-group">
                        <label class="small text-uppercase text-muted">What went well</label>
                        <textarea name="what_went_well" rows="3" class="form-control" {{ $canEdit ? '' : 'readonly' }}>{{ old('what_went_well', $closeout->what_went_well) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="small text-uppercase text-muted">What was difficult</label>
                        <textarea name="what_was_difficult" rows="3" class="form-control" {{ $canEdit ? '' : 'readonly' }}>{{ old('what_was_difficult', $closeout->what_was_difficult) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="small text-uppercase text-muted">Recommendations for the next grant</label>
                        <textarea name="recommendations" rows="3" class="form-control" {{ $canEdit ? '' : 'readonly' }}>{{ old('recommendations', $closeout->recommendations) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="small text-uppercase text-muted">Donor feedback</label>
                        <textarea name="donor_feedback" rows="2" class="form-control" {{ $canEdit ? '' : 'readonly' }}>{{ old('donor_feedback', $closeout->donor_feedback) }}</textarea>
                    </div>

                    @if($canEdit)
                    <button class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save progress</button>
                    @endif
                </form>
            </div>
        </div>

    </div>

    <div class="col-md-4">

        <div class="card">
            <div class="card-header"><h3 class="card-title">Where the award stands</h3></div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span>Ended</span>
                    <strong>{{ $award->end_date->format('j M Y') }}
                        @if($readiness['days_since_end'] > 0)
                            <small class="text-muted">({{ $readiness['days_since_end'] }} days ago)</small>
                        @endif
                    </strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Reports outstanding</span>
                    <strong class="{{ $readiness['outstanding_reports'] > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $readiness['outstanding_reports'] }}
                    </strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Sub-awards still open</span>
                    <strong class="{{ $readiness['open_sub_awards'] > 0 ? 'text-warning' : 'text-success' }}">
                        {{ $readiness['open_sub_awards'] }}
                    </strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Allocated</span>
                    <strong>{{ $award->currency }} {{ number_format($readiness['allocated'], 2) }}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Spent</span>
                    <strong>{{ $award->currency }} {{ number_format($readiness['spent'], 2) }}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Balance</span>
                    <strong class="{{ $readiness['balance'] > 0 ? 'text-warning' : '' }}">
                        {{ $award->currency }} {{ number_format($readiness['balance'], 2) }}
                    </strong>
                </li>
            </ul>
        </div>

        @if($closeout->status === 'completed')
            <div class="alert alert-success">
                <i class="fas fa-check-circle mr-1"></i>
                Closed out by {{ $closeout->completedBy?->name }} on {{ $closeout->completed_at?->format('j M Y') }}.
                @if($closeout->archive_reference)
                    <div class="small mt-1">Archive: {{ $closeout->archive_reference }}</div>
                @endif
            </div>
        @elseif($canEdit)
            <div class="card card-outline card-success">
                <div class="card-body">
                    @if($closeout->readyToComplete())
                        <p class="small">Every step is ticked and the lessons are recorded. Signing off archives the project and marks the award completed.</p>
                        <form method="POST" action="{{ route('awards.closeout.complete', $award) }}">
                            @csrf
                            <button class="btn btn-success btn-block">
                                <i class="fas fa-flag-checkered mr-1"></i>Sign off closeout
                            </button>
                        </form>
                    @else
                        <p class="small mb-2"><strong>Still outstanding:</strong></p>
                        <ul class="small pl-3 mb-0">
                            @foreach($closeout->outstandingSteps() as $step)
                                <li>{{ $step }}</li>
                            @endforeach
                            @if(! $closeout->hasLessons())
                                <li>Lessons learned</li>
                            @endif
                        </ul>
                    @endif
                </div>
            </div>
        @endif

    </div>
</div>
@stop
