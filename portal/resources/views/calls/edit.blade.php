@extends('layouts.gmu')

@section('title', 'Edit call')
@section('crumb', 'Grant opportunities')

@section('content')
<x-ui.page-head title="Edit funding call" :lede="$call->title">
    <x-slot:actions>
        <a href="{{ route('calls.show', $call) }}" class="btn">View call</a>
    </x-slot:actions>
</x-ui.page-head>

<form method="POST" action="{{ route('calls.update', $call) }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="grid grid-wide">
        <div class="stack">
            <x-ui.card title="The opportunity">
                <div class="form-grid">
                    <x-ui.field label="Title" name="title" required wide>
                        <input name="title" class="input" value="{{ old('title', $call->title) }}" required maxlength="255">
                    </x-ui.field>

                    <x-ui.field label="Donor" name="donor_id" required>
                        <select name="donor_id" class="select" required>
                            @foreach($donors as $donor)
                                <option value="{{ $donor->id }}" @selected(old('donor_id', $call->donor_id) == $donor->id)>{{ $donor->name }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Theme" name="theme">
                        <input name="theme" class="input" value="{{ old('theme', $call->theme) }}">
                    </x-ui.field>

                    <x-ui.field label="Description" name="description" wide>
                        <textarea name="description" rows="4" class="textarea">{{ old('description', $call->description) }}</textarea>
                    </x-ui.field>

                    <x-ui.field label="Eligibility" name="eligibility" wide>
                        <textarea name="eligibility" rows="3" class="textarea">{{ old('eligibility', $call->eligibility) }}</textarea>
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card title="Budget and dates">
                <div class="form-grid">
                    <x-ui.field label="Estimated budget" name="estimated_budget">
                        <input type="number" step="0.01" min="0" name="estimated_budget" class="input"
                               value="{{ old('estimated_budget', $call->estimated_budget) }}">
                    </x-ui.field>

                    <x-ui.field label="Currency" name="currency" required>
                        <select name="currency" class="select" required>
                            @foreach(\App\Support\Money::supported() as $code)
                                <option value="{{ $code }}" @selected(old('currency', $call->currency) === $code)>{{ $code }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Solicitation number" name="solicitation_number">
                        <input name="solicitation_number" class="input" value="{{ old('solicitation_number', $call->solicitation_number) }}">
                    </x-ui.field>

                    <x-ui.field label="Opens" name="open_date">
                        <input type="date" name="open_date" class="input" value="{{ old('open_date', $call->open_date?->toDateString()) }}">
                    </x-ui.field>

                    <x-ui.field label="Deadline" name="deadline" required>
                        <input type="date" name="deadline" class="input" value="{{ old('deadline', $call->deadline?->toDateString()) }}" required>
                    </x-ui.field>
                </div>
            </x-ui.card>
        </div>

        <div class="stack">
            <x-ui.card title="Publication">
                <div class="stack-sm">
                    <x-ui.field label="Status" name="status" required>
                        <select name="status" class="select" required>
                            @foreach(['open' => 'Open', 'draft' => 'Draft', 'closed' => 'Closed', 'archived' => 'Archived'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $call->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Origin" name="initiation_type" required>
                        <select name="initiation_type" class="select" required>
                            <option value="gmu_posted" @selected(old('initiation_type', $call->initiation_type) === 'gmu_posted')>Found by the GMU</option>
                            <option value="researcher_initiated" @selected(old('initiation_type', $call->initiation_type) === 'researcher_initiated')>Brought by a researcher</option>
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Replace call document" name="call_document">
                        @if($call->call_document)
                            <a href="{{ route('calls.document', $call) }}" class="btn btn-sm" style="margin-bottom:8px;">
                                <i class="fas fa-file"></i> Current document
                            </a>
                        @endif
                        <input type="file" name="call_document" class="input" accept=".pdf,.doc,.docx">
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="form-actions">
                    <button class="btn btn-primary">Save changes</button>
                    <a href="{{ route('calls.show', $call) }}" class="btn">Cancel</a>
                </div>
            </x-ui.card>

            @can('delete', $call)
                <x-ui.card title="Withdraw this call" accent="danger">
                    <p class="small muted" style="line-height:1.7;">
                        Withdrawing removes the call from the portal. Proposals already registered against it are kept.
                    </p>
                    <form method="POST" action="{{ route('calls.destroy', $call) }}"
                          onsubmit="return confirm('Withdraw this call?')" style="margin-top:12px;">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-block">Withdraw call</button>
                    </form>
                </x-ui.card>
            @endcan
        </div>
    </div>
</form>
@endsection
