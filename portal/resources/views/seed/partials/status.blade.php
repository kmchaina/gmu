@php $tone = $application->statusTone(); @endphp
<span class="pill {{ $tone ? 'pill-' . $tone : '' }}">{{ $application->statusLabel() }}</span>
