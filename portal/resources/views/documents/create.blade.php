@extends('layouts.gmu')

@section('title', 'Upload a document')
@section('crumb', 'Library')

@section('content')
<x-ui.page-head
    title="Upload a document"
    lede="Files are stored privately and served only to people allowed to see them. Retention is set from the category." />

<form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="grid grid-wide">
        <x-ui.card title="The document">
            <div class="form-grid">
                <x-ui.field label="Title" name="title" required wide>
                    <input name="title" class="input" value="{{ old('title') }}" required maxlength="255">
                </x-ui.field>

                <x-ui.field label="Category" name="category" required help="Sets the default retention period">
                    <select name="category" class="select" required>
                        @foreach(['sop' => 'SOP (5 years)', 'template' => 'Template (3 years)', 'policy' => 'Policy (5 years)', 'contract' => 'Contract (10 years)', 'report' => 'Report (7 years)', 'audit' => 'Audit evidence (7 years)', 'other' => 'Other (5 years)'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field label="Version" name="version" required>
                    <input type="number" name="version" min="1" class="input" value="{{ old('version', 1) }}" required>
                </x-ui.field>

                <x-ui.field label="Description" name="description" wide>
                    <textarea name="description" rows="3" class="textarea">{{ old('description') }}</textarea>
                </x-ui.field>

                <x-ui.field label="File" name="file" required wide
                            help="PDF, Word, Excel, PowerPoint, images or a zip archive — up to 20 MB">
                    <input type="file" name="file" class="input" required>
                </x-ui.field>
            </div>
        </x-ui.card>

        <div class="stack">
            <x-ui.card title="Who may see it">
                <label class="check">
                    <input type="checkbox" name="is_public" value="1" @checked(old('is_public'))>
                    <span>Any member of staff may open this</span>
                </label>
                <p class="small muted" style="margin-top:12px; line-height:1.7;">
                    Leave unticked for anything confidential — a signed contract, an audit response, a financial
                    statement. Restricted documents are visible to you and to GMU officers only.
                </p>
            </x-ui.card>

            <x-ui.card>
                <div class="form-actions">
                    <button class="btn btn-primary"><i class="fas fa-upload"></i> Upload</button>
                    <a href="{{ route('documents.index') }}" class="btn">Cancel</a>
                </div>
            </x-ui.card>
        </div>
    </div>
</form>
@endsection
