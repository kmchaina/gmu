@extends('layouts.gmu')

@section('title', 'Edit donor')
@section('crumb', 'Grant opportunities')

@section('content')
<x-ui.page-head title="Edit donor" :lede="$donor->name">
    <x-slot:actions>
        <a href="{{ route('donors.show', $donor) }}" class="btn">View donor</a>
    </x-slot:actions>
</x-ui.page-head>

<form method="POST" action="{{ route('donors.update', $donor) }}">
    @csrf @method('PUT')

    <div class="grid grid-wide">
        <x-ui.card title="The funder">
            <div class="form-grid">
                <x-ui.field label="Name" name="name" required wide>
                    <input name="name" class="input" value="{{ old('name', $donor->name) }}" required maxlength="255">
                </x-ui.field>

                <x-ui.field label="Acronym" name="acronym">
                    <input name="acronym" class="input" value="{{ old('acronym', $donor->acronym) }}" maxlength="20">
                </x-ui.field>

                <x-ui.field label="Type" name="type" required>
                    <select name="type" class="select" required>
                        @foreach(['bilateral' => 'Bilateral', 'multilateral' => 'Multilateral', 'foundation' => 'Foundation', 'government' => 'Government', 'private' => 'Private sector', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $donor->type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field label="Country" name="country">
                    <input name="country" class="input" value="{{ old('country', $donor->country) }}" maxlength="100">
                </x-ui.field>

                <x-ui.field label="Website" name="website">
                    <input type="url" name="website" class="input" value="{{ old('website', $donor->website) }}">
                </x-ui.field>

                <x-ui.field label="Grants contact" name="contact_email">
                    <input type="email" name="contact_email" class="input" value="{{ old('contact_email', $donor->contact_email) }}">
                </x-ui.field>

                <x-ui.field label="Notes" name="notes" wide>
                    <textarea name="notes" rows="3" class="textarea">{{ old('notes', $donor->notes) }}</textarea>
                </x-ui.field>
            </div>
        </x-ui.card>

        <div class="stack">
            <x-ui.card>
                <div class="form-actions">
                    <button class="btn btn-primary">Save changes</button>
                    <a href="{{ route('donors.show', $donor) }}" class="btn">Cancel</a>
                </div>
            </x-ui.card>

            @can('delete', $donor)
                <x-ui.card title="Remove this donor" accent="danger">
                    <p class="small muted" style="line-height:1.7;">
                        Only possible while no calls or awards reference them — the records that depend on a donor
                        must not disappear with it.
                    </p>
                    <form method="POST" action="{{ route('donors.destroy', $donor) }}"
                          onsubmit="return confirm('Remove this donor?')" style="margin-top:12px;">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-block">Remove donor</button>
                    </form>
                </x-ui.card>
            @endcan
        </div>
    </div>
</form>
@endsection
