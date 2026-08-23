@props(['label' => null, 'name' => null, 'help' => null, 'wide' => false, 'required' => false])

<div class="field {{ $wide ? 'wide' : '' }}">
    @if($label)
        <label class="label" @if($name) for="{{ $name }}" @endif>
            {{ $label }}@if($required)<span style="color:var(--danger)"> *</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if($help)<span class="help">{{ $help }}</span>@endif
    @if($name)@error($name)<span class="error">{{ $message }}</span>@enderror @endif
</div>
