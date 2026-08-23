<form method="POST" action="{{ $route }}">
    @csrf
    @if($method !== 'POST')@method($method)@endif

    <div class="grid grid-wide">
        <div class="stack">
            <x-ui.card title="The text">
                <div class="form-grid">
                    <x-ui.field label="Title" name="title" required wide>
                        <input name="title" class="input" value="{{ old('title', $item?->title) }}" required maxlength="255"
                               placeholder="NIMR institutional capability statement">
                    </x-ui.field>

                    <x-ui.field label="Content" name="content" required wide
                                help="Write it as it would appear in a proposal — at least 20 characters">
                        <textarea name="content" rows="14" class="textarea" required>{{ old('content', $item?->content) }}</textarea>
                    </x-ui.field>
                </div>
            </x-ui.card>
        </div>

        <div class="stack">
            <x-ui.card title="Classification">
                <div class="stack-sm">
                    <x-ui.field label="Category" name="category" required>
                        <select name="category" class="select" required>
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', $item?->category) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field label="Relevant funders" name="funder_relevance" help="Comma separated, or leave blank for any">
                        <input name="funder_relevance" class="input" value="{{ old('funder_relevance', $item?->funder_relevance) }}"
                               placeholder="NIH,Wellcome,EDCTP">
                    </x-ui.field>

                    <x-ui.field label="Tags" name="tags" help="Comma separated, used by search">
                        <input name="tags" class="input"
                               value="{{ old('tags', $item?->tags ? implode(', ', $item->tags) : '') }}"
                               placeholder="institutional, boilerplate">
                    </x-ui.field>

                    <x-ui.field label="When to use it" name="usage_notes"
                                help="Guidance for whoever reaches for this next">
                        <textarea name="usage_notes" rows="3" class="textarea">{{ old('usage_notes', $item?->usage_notes) }}</textarea>
                    </x-ui.field>
                </div>
            </x-ui.card>

            @if($item && ! $item->is_approved)
                <div class="alert alert-warn" style="margin:0;">
                    <i class="fas fa-clock"></i>
                    <span>This block is awaiting the Coordinator's approval before it circulates.</span>
                </div>
            @endif

            <x-ui.card>
                <div class="form-actions">
                    <button class="btn btn-primary">{{ $item ? 'Save changes' : 'Add to the library' }}</button>
                    <a href="{{ $item ? route('library.show', $item) : route('library.index') }}" class="btn">Cancel</a>
                </div>
            </x-ui.card>
        </div>
    </div>
</form>

@if($item)
    @can('delete', $item)
        <div style="max-width:420px; margin-top:20px;">
            <x-ui.card title="Remove this block" accent="danger">
                <form method="POST" action="{{ route('library.destroy', $item) }}"
                      onsubmit="return confirm('Remove this library block?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-block">Remove block</button>
                </form>
            </x-ui.card>
        </div>
    @endcan
@endif
