@extends('layouts.gmu')

@section('title', 'Closeout')
@section('crumb', $award->contract_number)

@section('content')
@php use App\Models\AwardCloseout; @endphp

<div class="page-head">
    <div style="min-width:0;">
        <a href="{{ route('awards.show', $award) }}" class="small">&larr; {{ $award->contract_number }}</a>
        <h1 style="margin-top:8px;">Closeout &amp; lessons learned</h1>
        <p class="lede">{{ $award->title }}</p>
    </div>
    <div class="page-actions">
        <x-ui.pill :tone="$closeout->status === 'completed' ? 'ok' : ($closeout->progress() > 0 ? 'warn' : null)">
            {{ Str::headline($closeout->status) }} — {{ $closeout->progress() }}%
        </x-ui.pill>
    </div>
</div>

<div class="stack">

    <div class="stats">
        <x-ui.stat label="Award ended" :value="$award->end_date?->format('d M Y')"
                   :note="$readiness['days_since_end'] > 0 ? $readiness['days_since_end'] . ' days ago' : 'Still running'" />
        <x-ui.stat label="Reports outstanding" :value="$readiness['outstanding_reports']"
                   :tone="$readiness['outstanding_reports'] > 0 ? 'danger' : 'ok'"
                   note="Must be filed before sign-off" />
        <x-ui.stat label="Sub-awards open" :value="$readiness['open_sub_awards']"
                   :tone="$readiness['open_sub_awards'] > 0 ? 'warn' : 'ok'"
                   note="Partners still to close out" />
        <x-ui.stat label="Balance" :value="$award->currency . ' ' . number_format($readiness['balance'], 0)"
                   :tone="$readiness['balance'] > 0 ? 'warn' : null"
                   :note="'Spent ' . number_format($readiness['spent'], 0) . ' of ' . number_format($readiness['allocated'], 0)" />
    </div>

    <form method="POST" action="{{ route('awards.closeout.update', $award) }}">
        @csrf @method('PATCH')

        <div class="grid grid-wide">
            <div class="stack">

                <x-ui.card title="Closeout checklist" note="Workflow D of the business process framework">
                    <div class="meter" style="margin-bottom:20px;"><span style="width: {{ $closeout->progress() }}%"></span></div>

                    <div class="stack-sm">
                        @foreach(AwardCloseout::STEPS as $column => $label)
                            <label class="check" style="padding:9px 12px; border:1px solid var(--line); border-radius:8px;
                                   background: {{ $closeout->{$column} ? 'var(--ok-bg)' : 'var(--surface)' }};
                                   border-color: {{ $closeout->{$column} ? 'var(--ok-line)' : 'var(--line)' }};">
                                <input type="checkbox" name="steps[]" value="{{ $column }}"
                                       @checked($closeout->{$column}) @disabled(! $canEdit)>
                                <span style="flex:1;">{{ $label }}</span>
                                @if($closeout->{$column})
                                    <span class="tiny muted">{{ $closeout->{$column}->format('d M Y') }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                </x-ui.card>

                <x-ui.card title="Final reconciliation" note="The figures the donor will ask about">
                    <div class="form-grid">
                        <x-ui.field label="Total expenditure ({{ $award->currency }})" name="total_expenditure">
                            <input type="number" step="0.01" min="0" name="total_expenditure" class="input"
                                   value="{{ old('total_expenditure', $closeout->total_expenditure ?? $readiness['spent']) }}" @readonly(! $canEdit)>
                        </x-ui.field>

                        <x-ui.field label="Unspent balance" name="unspent_balance">
                            <input type="number" step="0.01" name="unspent_balance" class="input"
                                   value="{{ old('unspent_balance', $closeout->unspent_balance ?? $readiness['balance']) }}" @readonly(! $canEdit)>
                        </x-ui.field>

                        <x-ui.field label="Returned to donor" name="funds_returned">
                            <input type="number" step="0.01" min="0" name="funds_returned" class="input"
                                   value="{{ old('funds_returned', $closeout->funds_returned) }}" @readonly(! $canEdit)>
                        </x-ui.field>

                        <x-ui.field label="Date returned" name="funds_returned_on">
                            <input type="date" name="funds_returned_on" class="input"
                                   value="{{ old('funds_returned_on', $closeout->funds_returned_on?->toDateString()) }}" @readonly(! $canEdit)>
                        </x-ui.field>

                        <x-ui.field label="Archive reference" name="archive_reference" wide
                                    help="Where the project file now lives">
                            <input name="archive_reference" class="input"
                                   value="{{ old('archive_reference', $closeout->archive_reference) }}" @readonly(! $canEdit)>
                        </x-ui.field>
                    </div>
                </x-ui.card>

                <x-ui.card title="Lessons learned"
                           note="The point of closeout — write for a colleague who was not on this grant">
                    <div class="form-grid">
                        <x-ui.field label="What went well" name="what_went_well" wide>
                            <textarea name="what_went_well" rows="3" class="textarea" @readonly(! $canEdit)>{{ old('what_went_well', $closeout->what_went_well) }}</textarea>
                        </x-ui.field>

                        <x-ui.field label="What was difficult" name="what_was_difficult" wide>
                            <textarea name="what_was_difficult" rows="3" class="textarea" @readonly(! $canEdit)>{{ old('what_was_difficult', $closeout->what_was_difficult) }}</textarea>
                        </x-ui.field>

                        <x-ui.field label="Recommendations for the next grant" name="recommendations" wide>
                            <textarea name="recommendations" rows="3" class="textarea" @readonly(! $canEdit)>{{ old('recommendations', $closeout->recommendations) }}</textarea>
                        </x-ui.field>

                        <x-ui.field label="Donor feedback" name="donor_feedback" wide>
                            <textarea name="donor_feedback" rows="2" class="textarea" @readonly(! $canEdit)>{{ old('donor_feedback', $closeout->donor_feedback) }}</textarea>
                        </x-ui.field>
                    </div>

                    @if($canEdit)
                        <div class="form-actions"><button class="btn btn-primary"><i class="fas fa-save"></i> Save progress</button></div>
                    @endif
                </x-ui.card>
            </div>

            <div class="stack">
                @if($closeout->status === 'completed')
                    <x-ui.card title="Signed off" accent="ok">
                        <p class="small muted" style="line-height:1.7;">
                            Closed out by {{ $closeout->completedBy?->name }} on {{ $closeout->completed_at?->format('d M Y') }}.
                            @if($closeout->archive_reference)
                                <br><br>Archive reference <span class="mono">{{ $closeout->archive_reference }}</span>.
                            @endif
                        </p>
                    </x-ui.card>
                @elseif($canEdit)
                    <x-ui.card title="Sign off" accent="{{ $closeout->readyToComplete() ? 'ok' : null }}">
                        @if($closeout->readyToComplete())
                            <p class="small muted" style="line-height:1.7;">
                                Every step is ticked and the lessons are recorded. Signing off archives the project and
                                marks the award completed.
                            </p>
                        @else
                            <p class="small muted" style="margin-bottom:10px;">Still outstanding:</p>
                            <ul class="small muted" style="margin:0; padding-left:18px; line-height:1.8;">
                                @foreach($closeout->outstandingSteps() as $step)<li>{{ $step }}</li>@endforeach
                                @if(! $closeout->hasLessons())<li>Lessons learned</li>@endif
                            </ul>
                        @endif
                    </x-ui.card>

                    @if($closeout->readyToComplete())
                        <form method="POST" action="{{ route('awards.closeout.complete', $award) }}" onsubmit="return confirm('Sign off closeout? This archives the project and completes the award.')">
                            @csrf
                            <button class="btn btn-primary btn-block"><i class="fas fa-flag-checkered"></i> Sign off closeout</button>
                        </form>
                    @endif
                @endif

                <x-ui.card title="Where the award stands">
                    <div class="stack-sm small">
                        <div class="row-between"><span class="muted">Ethics records open</span><span>{{ $readiness['ethics_open'] }}</span></div>
                        <div class="row-between"><span class="muted">Reports outstanding</span><span>{{ $readiness['outstanding_reports'] }}</span></div>
                        <div class="row-between"><span class="muted">Sub-awards open</span><span>{{ $readiness['open_sub_awards'] }}</span></div>
                        <div class="row-between"><span class="muted">Allocated</span><span class="mono">{{ number_format($readiness['allocated'], 2) }}</span></div>
                        <div class="row-between"><span class="muted">Spent</span><span class="mono">{{ number_format($readiness['spent'], 2) }}</span></div>
                        <div class="row-between"><span class="muted">Balance</span><span class="mono">{{ number_format($readiness['balance'], 2) }}</span></div>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </form>
</div>
@endsection
