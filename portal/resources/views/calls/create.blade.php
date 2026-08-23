@extends('layouts.gmu')

@section('title', 'Post a funding call')
@section('crumb', 'Grant opportunities')

@section('content')
<x-ui.page-head
    title="Post a funding call"
    lede="Once the status is open, every researcher is notified and can register interest." />

<form method="POST" action="{{ route('calls.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="grid grid-wide">
        <div class="stack">
            <x-ui.card title="The opportunity">
                <div class="form-grid">
                    <x-ui.field label="Title" name="title" required wide>
                        <input name="title" class="input @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" required maxlength="255">
                    </x-ui.field>

                    <x-ui.field label="Donor" name="donor_id" required>
                        <select name="donor_id" class="select" required>
                            <option value="">Choose a donor…</option>
                            @foreach($donors as $donor)
                                <option value="{{ $donor->id }}" @selected(old('donor_id') == $donor->id)>{{ $donor->name }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Theme" name="theme" help="Used by the matching engine">
                        <input name="theme" class="input" value="{{ old('theme') }}" placeholder="Malaria, HIV, One Health…">
                    </x-ui.field>

                    <x-ui.field label="Description" name="description" wide>
                        <textarea name="description" rows="4" class="textarea">{{ old('description') }}</textarea>
                    </x-ui.field>

                    <x-ui.field label="Eligibility" name="eligibility" wide help="Who may apply, and any institutional limits">
                        <textarea name="eligibility" rows="3" class="textarea">{{ old('eligibility') }}</textarea>
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card title="Budget and dates">
                <div class="form-grid">
                    <x-ui.field label="Estimated budget" name="estimated_budget">
                        <input type="number" step="0.01" min="0" name="estimated_budget" class="input" value="{{ old('estimated_budget') }}">
                    </x-ui.field>

                    <x-ui.field label="Currency" name="currency" required>
                        <select name="currency" class="select" required>
                            @foreach(\App\Support\Money::supported() as $code)
                                <option value="{{ $code }}" @selected(old('currency', \App\Support\Money::base()) === $code)>{{ $code }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Solicitation number" name="solicitation_number">
                        <input name="solicitation_number" class="input" value="{{ old('solicitation_number') }}">
                    </x-ui.field>

                    <x-ui.field label="Opens" name="open_date">
                        <input type="date" name="open_date" class="input" value="{{ old('open_date') }}">
                    </x-ui.field>

                    <x-ui.field label="Deadline" name="deadline" required help="Must be in the future">
                        <input type="date" name="deadline" class="input" value="{{ old('deadline') }}" required
                               min="{{ now()->addDay()->toDateString() }}">
                    </x-ui.field>
                </div>
            </x-ui.card>
        </div>

        <div class="stack">
            <x-ui.card title="Publication">
                <div class="stack-sm">
                    <x-ui.field label="Status" name="status" required>
                        <select name="status" class="select" required>
                            <option value="open" @selected(old('status', 'open') === 'open')>Open — notify researchers</option>
                            <option value="draft" @selected(old('status') === 'draft')>Draft — not circulated</option>
                            <option value="closed" @selected(old('status') === 'closed')>Closed</option>
                            <option value="archived" @selected(old('status') === 'archived')>Archived</option>
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Origin" name="initiation_type" required>
                        <select name="initiation_type" class="select" required>
                            <option value="gmu_posted" @selected(old('initiation_type', 'gmu_posted') === 'gmu_posted')>Found by the GMU</option>
                            <option value="researcher_initiated" @selected(old('initiation_type') === 'researcher_initiated')>Brought by a researcher</option>
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Call document" name="call_document" help="PDF or Word, up to 10 MB">
                        <input type="file" name="call_document" class="input" accept=".pdf,.doc,.docx">
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="form-actions">
                    <button class="btn btn-primary"><i class="fas fa-bullhorn"></i> Post the call</button>
                    <a href="{{ route('calls.index') }}" class="btn">Cancel</a>
                </div>
            </x-ui.card>
        </div>
    </div>
</form>
@endsection
