@props(['tone' => null])

<span {{ $attributes->merge(['class' => 'pill' . ($tone ? " pill-{$tone}" : '')]) }}>{{ $slot }}</span>
