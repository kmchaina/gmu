@props(['value' => 0, 'label' => null, 'showValue' => true])

@php
    $pct  = max(0, min(100, (float) $value));
    $tone = $value > 100 ? 'danger' : ($value > 85 ? 'warn' : '');
@endphp

<div>
    @if($label)
        <div class="meter-label"><span>{{ $label }}</span>@if($showValue)<b>{{ round($value, 1) }}%</b>@endif</div>
    @endif
    <div class="meter {{ $tone }}"><span style="width: {{ $pct }}%"></span></div>
</div>
