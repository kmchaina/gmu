@props(['title', 'lede' => null])

<div class="page-head">
    <div>
        <h1>{{ $title }}</h1>
        @if($lede)<p class="lede">{{ $lede }}</p>@endif
    </div>
    @if(isset($actions))
        <div class="page-actions">{{ $actions }}</div>
    @endif
</div>
