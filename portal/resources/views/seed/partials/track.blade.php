@php
    $current = $application->trackIndex();
    $stopped = in_array($application->status, \App\Support\SeedGrant::CLOSED_STATUSES, true);
@endphp
<ol class="track" aria-label="Where the application is">
    @foreach(\App\Support\SeedGrant::TRACK as $i => [$title, $sub])
        @php
            $class = $i < $current ? 'done' : ($i === $current ? ($stopped ? 'stopped' : ($application->status === \App\Support\SeedGrant::AWARDED ? 'done' : 'now')) : 'todo');
        @endphp
        <li class="{{ $class }}" @if($i === $current) aria-current="step" @endif>
            <span class="ln"></span>
            <span class="t">{{ $title }}</span>
            <span class="s">{{ $i === $current ? $application->statusLabel() : $sub }}</span>
        </li>
    @endforeach
</ol>
