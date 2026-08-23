@extends('layouts.gmu')

@section('title', 'Edit profile')
@section('crumb', 'Researcher profile')

@section('content')
@php
    $quals = old('qual_degree') ? array_keys(old('qual_degree')) : range(0, max(2, count($profile->qualifications ?? []) - 1));
    $pubs  = old('pub_title') ? array_keys(old('pub_title')) : range(0, max(2, count($profile->publications ?? []) - 1));
    $existingQuals = collect($profile->qualifications ?? [])->values();
    $existingPubs  = collect($profile->publications ?? [])->values();
@endphp

<x-ui.page-head
    title="Edit profile"
    :lede="$user->name . ' · what the matching engine reads when a call comes in'">
    <x-slot:actions>
        <a href="{{ route('researchers.show', $user) }}" class="btn">View profile</a>
    </x-slot:actions>
</x-ui.page-head>

<form method="POST" action="{{ route('researchers.update', $user) }}">
    @csrf @method('PUT')

    <div class="grid grid-wide">
        <div class="stack">
            <x-ui.card title="About">
                <div class="form-grid">
                    <x-ui.field label="Title" name="title">
                        <input name="title" class="input" value="{{ old('title', $profile->title) }}" maxlength="20" placeholder="Dr.">
                    </x-ui.field>

                    <x-ui.field label="Position" name="position">
                        <input name="position" class="input" value="{{ old('position', $profile->position) }}"
                               placeholder="Principal Research Scientist">
                    </x-ui.field>

                    <x-ui.field label="Department" name="department">
                        <input name="department" class="input" value="{{ old('department', $profile->department) }}">
                    </x-ui.field>

                    <x-ui.field label="Research centre" name="research_center">
                        <input name="research_center" class="input" value="{{ old('research_center', $profile->research_center) }}">
                    </x-ui.field>

                    <x-ui.field label="Biography" name="bio" wide>
                        <textarea name="bio" rows="4" class="textarea" maxlength="3000">{{ old('bio', $profile->bio) }}</textarea>
                    </x-ui.field>

                    <x-ui.field label="Expertise areas" name="expertise_areas" wide
                                help="Comma separated. These are matched against call themes, so use the words a donor would use.">
                        <input name="expertise_areas" class="input"
                               value="{{ old('expertise_areas', collect($profile->expertise_areas ?? [])->implode(', ')) }}"
                               placeholder="Malaria, Vector control, Epidemiology">
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card title="Qualifications">
                @foreach($quals as $i)
                    @php $q = $existingQuals[$i] ?? []; @endphp
                    <div class="form-grid" style="margin-bottom:14px;">
                        <x-ui.field label="Degree">
                            <input name="qual_degree[{{ $i }}]" class="input" value="{{ old("qual_degree.$i", $q['degree'] ?? '') }}" placeholder="PhD">
                        </x-ui.field>
                        <x-ui.field label="Field">
                            <input name="qual_field[{{ $i }}]" class="input" value="{{ old("qual_field.$i", $q['field'] ?? '') }}" placeholder="Epidemiology">
                        </x-ui.field>
                        <x-ui.field label="Institution">
                            <input name="qual_institution[{{ $i }}]" class="input" value="{{ old("qual_institution.$i", $q['institution'] ?? '') }}">
                        </x-ui.field>
                        <x-ui.field label="Year">
                            <input name="qual_year[{{ $i }}]" type="number" min="1950" max="2100" class="input"
                                   value="{{ old("qual_year.$i", $q['year'] ?? '') }}">
                        </x-ui.field>
                    </div>
                @endforeach
                <p class="help">Leave a row blank to drop it. Qualifications in a call's theme add to the match score.</p>
            </x-ui.card>

            <x-ui.card title="Publications">
                @foreach($pubs as $i)
                    @php $p = $existingPubs[$i] ?? []; @endphp
                    <div class="form-grid" style="margin-bottom:14px;">
                        <x-ui.field label="Title" wide>
                            <input name="pub_title[{{ $i }}]" class="input" value="{{ old("pub_title.$i", $p['title'] ?? '') }}">
                        </x-ui.field>
                        <x-ui.field label="Journal">
                            <input name="pub_journal[{{ $i }}]" class="input" value="{{ old("pub_journal.$i", $p['journal'] ?? '') }}">
                        </x-ui.field>
                        <x-ui.field label="Year">
                            <input name="pub_year[{{ $i }}]" type="number" min="1950" max="2100" class="input"
                                   value="{{ old("pub_year.$i", $p['year'] ?? '') }}">
                        </x-ui.field>
                        <x-ui.field label="DOI">
                            <input name="pub_doi[{{ $i }}]" class="input mono" value="{{ old("pub_doi.$i", $p['doi'] ?? '') }}">
                        </x-ui.field>
                    </div>
                @endforeach
            </x-ui.card>
        </div>

        <div class="stack">
            <x-ui.card title="Contact and links">
                <div class="stack-sm">
                    <x-ui.field label="Phone" name="phone" help="Used for SMS deadline alerts">
                        <input name="phone" class="input" value="{{ old('phone', $profile->phone) }}" maxlength="30">
                    </x-ui.field>

                    <x-ui.field label="ORCID iD" name="orcid_id" help="Format 0000-0002-1825-0097">
                        <input name="orcid_id" class="input mono" value="{{ old('orcid_id', $profile->orcid_id) }}"
                               pattern="\d{4}-\d{4}-\d{4}-\d{3}[\dX]">
                    </x-ui.field>

                    <x-ui.field label="LinkedIn" name="linkedin_url">
                        <input type="url" name="linkedin_url" class="input" value="{{ old('linkedin_url', $profile->linkedin_url) }}">
                    </x-ui.field>

                    <x-ui.field label="ResearchGate" name="researchgate_url">
                        <input type="url" name="researchgate_url" class="input" value="{{ old('researchgate_url', $profile->researchgate_url) }}">
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="form-actions">
                    <button class="btn btn-primary">Save profile</button>
                    <a href="{{ route('researchers.show', $user) }}" class="btn">Cancel</a>
                </div>
            </x-ui.card>
        </div>
    </div>
</form>
@endsection
