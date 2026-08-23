@props(['icon' => 'fa-inbox', 'title' => null])

<div class="empty">
    <i class="fas {{ $icon }}"></i>
    @if($title)<div style="font-size:14px; font-weight:600; margin-bottom:6px;">{{ $title }}</div>@endif
    <p>{{ $slot }}</p>
    @if(isset($action))<div style="margin-top:16px;">{{ $action }}</div>@endif
</div>
