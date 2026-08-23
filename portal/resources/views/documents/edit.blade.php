@extends('layouts.gmu')

@section('title', 'Edit document')
@section('crumb', 'Library')

@section('content')
<x-ui.page-head title="Edit document" :lede="$document->title">
    <x-slot:actions>
        <a href="{{ route('documents.show', $document) }}" class="btn"><i class="fas fa-download"></i> Download</a>
    </x-slot:actions>
</x-ui.page-head>

<form method="POST" action="{{ route('documents.update', $document) }}">
    @csrf @method('PUT')

    <div class="grid grid-wide">
        <x-ui.card title="The document">
            <div class="form-grid">
                <x-ui.field label="Title" name="title" required wide>
                    <input name="title" class="input" value="{{ old('title', $document->title) }}" required maxlength="255">
                </x-ui.field>

                <x-ui.field label="Category" name="category" required>
                    <select name="category" class="select" required>
                        @foreach(['sop' => 'SOP', 'template' => 'Template', 'policy' => 'Policy', 'contract' => 'Contract', 'report' => 'Report', 'audit' => 'Audit evidence', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('category', $document->category) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field label="Version" name="version" required>
                    <input type="number" name="version" min="1" class="input" value="{{ old('version', $document->version) }}" required>
                </x-ui.field>

                <x-ui.field label="Retention (years)" name="retention_years"
                            help="Leave as is to keep the default for this category">
                    <input type="number" name="retention_years" min="1" max="50" class="input"
                           value="{{ old('retention_years', $document->retention_years) }}">
                </x-ui.field>

                <x-ui.field label="Description" name="description" wide>
                    <textarea name="description" rows="3" class="textarea">{{ old('description', $document->description) }}</textarea>
                </x-ui.field>
            </div>
        </x-ui.card>

        <div class="stack">
            <x-ui.card title="Who may see it">
                <label class="check">
                    <input type="checkbox" name="is_public" value="1" @checked(old('is_public', $document->is_public))>
                    <span>Any member of staff may open this</span>
                </label>
            </x-ui.card>

            <x-ui.card title="File on record">
                <div class="stack-sm small">
                    <div class="row-between"><span class="muted">Name</span><span>{{ $document->file_name }}</span></div>
                    <div class="row-between"><span class="muted">Size</span><span class="mono">{{ number_format(($document->file_size ?? 0) / 1024, 0) }} KB</span></div>
                    <div class="row-between"><span class="muted">Uploaded</span><span>{{ $document->created_at?->format('d M Y') }}</span></div>
                    <div class="row-between"><span class="muted">Retain until</span>
                        <span class="{{ $document->isDueForReview() ? 'text-warn' : '' }}">{{ $document->retain_until?->format('M Y') ?? '—' }}</span></div>
                </div>
                <p class="small muted-3" style="margin-top:12px;">
                    To replace the file itself, upload a new version and raise the version number.
                </p>
            </x-ui.card>

            <x-ui.card>
                <div class="form-actions">
                    <button class="btn btn-primary">Save changes</button>
                    <a href="{{ route('documents.index') }}" class="btn">Cancel</a>
                </div>
            </x-ui.card>

            @can('delete', $document)
                <x-ui.card title="Remove this document" accent="danger">
                    <p class="small muted">The record is soft-deleted and the file is removed from storage.</p>
                    <form method="POST" action="{{ route('documents.destroy', $document) }}"
                          onsubmit="return confirm('Remove this document and its file?')" style="margin-top:12px;">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-block">Remove document</button>
                    </form>
                </x-ui.card>
            @endcan
        </div>
    </div>
</form>
@endsection
