@props(['label', 'value', 'note' => null, 'tone' => null])

<div class="stat {{ $tone ? "is-{$tone}" : '' }}">
    <span class="stat-label">{{ $label }}</span>
    <span class="stat-value">{{ $value }}</span>
    @if($note)<span class="stat-note">{{ $note }}</span>@endif
</div>
