@props(['title' => null, 'note' => null, 'accent' => null, 'flush' => false])

<div {{ $attributes->merge(['class' => 'card' . ($accent ? " accent-{$accent}" : '')]) }}>
    @if($title || isset($action))
        <div class="card-head">
            <div>
                @if($title)<div class="card-title">{{ $title }}</div>@endif
                @if($note)<div class="card-note">{{ $note }}</div>@endif
            </div>
            @if(isset($action))<div class="row" style="gap:8px;">{{ $action }}</div>@endif
        </div>
    @endif

    @if($flush)
        {{ $slot }}
    @else
        <div class="card-body">{{ $slot }}</div>
    @endif
</div>
