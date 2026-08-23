@extends('layouts.gmu')

@section('title', 'Add a donor')
@section('crumb', 'Grant opportunities')

@section('content')
<x-ui.page-head
    title="Add a donor"
    lede="Every call and every award hangs off a funder, so this comes first." />

<form method="POST" action="{{ route('donors.store') }}">
    @csrf

    <div class="grid grid-wide">
        <x-ui.card title="The funder">
            <div class="form-grid">
                <x-ui.field label="Name" name="name" required wide>
                    <input name="name" class="input" value="{{ old('name') }}" required maxlength="255"
                           placeholder="National Institutes of Health">
                </x-ui.field>

                <x-ui.field label="Acronym" name="acronym" help="Shown in tables and charts">
                    <input name="acronym" class="input" value="{{ old('acronym') }}" maxlength="20" placeholder="NIH">
                </x-ui.field>

                <x-ui.field label="Type" name="type" required>
                    <select name="type" class="select" required>
                        @foreach(['bilateral' => 'Bilateral', 'multilateral' => 'Multilateral', 'foundation' => 'Foundation', 'government' => 'Government', 'private' => 'Private sector', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field label="Country" name="country">
                    <input name="country" class="input" value="{{ old('country') }}" maxlength="100">
                </x-ui.field>

                <x-ui.field label="Website" name="website">
                    <input type="url" name="website" class="input" value="{{ old('website') }}" placeholder="https://">
                </x-ui.field>

                <x-ui.field label="Grants contact" name="contact_email">
                    <input type="email" name="contact_email" class="input" value="{{ old('contact_email') }}">
                </x-ui.field>

                <x-ui.field label="Notes" name="notes" wide help="Anything the unit should remember about working with them">
                    <textarea name="notes" rows="3" class="textarea">{{ old('notes') }}</textarea>
                </x-ui.field>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="form-actions">
                <button class="btn btn-primary">Add donor</button>
                <a href="{{ route('donors.index') }}" class="btn">Cancel</a>
            </div>
        </x-ui.card>
    </div>
</form>
@endsection
