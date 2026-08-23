@extends('layouts.gmu')

@section('title', 'Register a proposal')
@section('crumb', 'Pipeline')

@section('content')
<x-ui.page-head
    title="Register a proposal"
    lede="Registering opens the three internal review tracks. Documents and team members are added on the proposal page afterwards." />

<form method="POST" action="{{ route('proposals.store') }}">
    @csrf

    <div class="grid grid-wide">
        <div class="stack">
            <x-ui.card title="The proposal">
                <div class="form-grid">
                    <x-ui.field label="Funding call" name="funding_call_id" required wide>
                        <select name="funding_call_id" class="select" required>
                            <option value="">Choose the call…</option>
                            @foreach($calls as $call)
                                <option value="{{ $call->id }}"
                                        @selected(old('funding_call_id', $selectedCall?->id) == $call->id)>
                                    {{ $call->title }} — closes {{ $call->deadline?->format('d M Y') }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Title" name="title" required wide>
                        <input name="title" class="input" value="{{ old('title') }}" required maxlength="255">
                    </x-ui.field>

                    <x-ui.field label="Abstract" name="abstract" wide help="A paragraph is enough at concept stage">
                        <textarea name="abstract" rows="4" class="textarea">{{ old('abstract') }}</textarea>
                    </x-ui.field>

                    <x-ui.field label="Lead PI" name="lead_pi_id" required>
                        <select name="lead_pi_id" class="select" required>
                            <option value="">Choose the lead…</option>
                            @foreach($pis as $pi)
                                <option value="{{ $pi->id }}" @selected(old('lead_pi_id') == $pi->id)>
                                    {{ $pi->name }}@if($pi->department) — {{ $pi->department }}@endif
                                </option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Target submission" name="target_submission_date">
                        <input type="date" name="target_submission_date" class="input"
                               value="{{ old('target_submission_date', $selectedCall?->deadline?->toDateString()) }}">
                    </x-ui.field>

                    <x-ui.field label="Requested budget" name="requested_budget">
                        <input type="number" step="0.01" min="0" name="requested_budget" class="input" value="{{ old('requested_budget') }}">
                    </x-ui.field>

                    <x-ui.field label="Currency" name="currency" required>
                        <select name="currency" class="select" required>
                            @foreach(\App\Support\Money::supported() as $code)
                                <option value="{{ $code }}"
                                        @selected(old('currency', $selectedCall?->currency ?? \App\Support\Money::base()) === $code)>{{ $code }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                </div>
            </x-ui.card>
        </div>

        <div class="stack">
            <x-ui.card title="Stage and origin">
                <div class="stack-sm">
                    <x-ui.field label="Stage" name="status" required>
                        <select name="status" class="select" required>
                            @foreach(['concept' => 'Concept', 'under_development' => 'In development', 'internal_review' => 'Internal review'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'concept') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Origin" name="initiation_type" required
                                help="Whether the GMU brought the call to the team, or the team came to the GMU">
                        <select name="initiation_type" class="select" required>
                            <option value="gmu_initiated" @selected(old('initiation_type', 'gmu_initiated') === 'gmu_initiated')>GMU initiated</option>
                            <option value="researcher_initiated" @selected(old('initiation_type') === 'researcher_initiated')>Researcher initiated</option>
                        </select>
                    </x-ui.field>
                </div>
            </x-ui.card>

            @if($selectedCall)
                <x-ui.card title="About the call">
                    <div class="stack-sm small">
                        <div class="row-between"><span class="muted">Donor</span><span>{{ $selectedCall->donor?->name }}</span></div>
                        <div class="row-between"><span class="muted">Deadline</span><span>{{ $selectedCall->deadline?->format('d M Y') }}</span></div>
                        <div class="row-between"><span class="muted">Indicative budget</span>
                            <span class="mono">{{ $selectedCall->estimated_budget ? $selectedCall->currency . ' ' . number_format($selectedCall->estimated_budget, 0) : '—' }}</span></div>
                    </div>
                </x-ui.card>
            @endif

            <x-ui.card>
                <div class="form-actions">
                    <button class="btn btn-primary"><i class="fas fa-plus"></i> Register proposal</button>
                    <a href="{{ route('proposals.index') }}" class="btn">Cancel</a>
                </div>
            </x-ui.card>
        </div>
    </div>
</form>
@endsection
