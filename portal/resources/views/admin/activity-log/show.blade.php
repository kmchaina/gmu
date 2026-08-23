@extends('layouts.gmu')

@section('title', 'Audit entry')
@section('crumb', 'Administration')

@section('content')
@php
    $old  = $log->old_values ?? [];
    $new  = $log->new_values ?? [];
    $keys = collect(array_keys($old + $new))->sort()->values();
@endphp

<div class="page-head">
    <div style="min-width:0;">
        <a href="{{ route('admin.activity-log.index') }}" class="small">&larr; Audit log</a>
        <h1 style="margin-top:8px;">{{ $log->description }}</h1>
        <div class="row" style="gap:12px; margin-top:10px;">
            <x-ui.pill :tone="match($log->action) { 'created' => 'ok', 'deleted' => 'danger', 'restored' => 'info', default => null }">
                {{ ucfirst($log->action) }}
            </x-ui.pill>
            <span class="small muted">{{ $log->created_at?->format('d M Y \a\t H:i') }}</span>
            <span class="small muted-3">{{ $log->user?->name ?? 'System' }}</span>
        </div>
    </div>
</div>

<div class="grid grid-wide">
    <x-ui.card title="What changed" note="Field by field, before and after" :flush="true">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Field</th><th>Before</th><th>After</th></tr></thead>
                <tbody>
                    @forelse($keys as $key)
                        @php
                            $before = $old[$key] ?? null;
                            $after  = $new[$key] ?? null;
                        @endphp
                        <tr>
                            <td class="strong">{{ Str::headline($key) }}</td>
                            <td class="small" style="color:var(--danger-ink);">
                                {{ is_scalar($before) || $before === null ? ($before ?? '—') : json_encode($before) }}
                            </td>
                            <td class="small" style="color:var(--ok-ink);">
                                {{ is_scalar($after) || $after === null ? ($after ?? '—') : json_encode($after) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-ui.empty icon="fa-equals">No field values were recorded for this entry.</x-ui.empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.card title="Context">
        <div class="stack-sm small">
            <div class="row-between"><span class="muted">Record</span><span>{{ class_basename($log->model_type) }} #{{ $log->model_id }}</span></div>
            <div class="row-between"><span class="muted">Label</span><span>{{ Str::limit($log->model_label, 26) }}</span></div>
            <div class="row-between"><span class="muted">Person</span><span>{{ $log->user?->name ?? 'System' }}</span></div>
            <div class="row-between"><span class="muted">When</span><span>{{ $log->created_at?->format('d M Y H:i:s') }}</span></div>
            <div class="row-between"><span class="muted">IP address</span><span class="mono">{{ $log->ip_address ?? '—' }}</span></div>
        </div>

        @if($log->user_agent)
            <div class="divider"></div>
            <div class="eyebrow" style="margin-bottom:6px;">User agent</div>
            <p class="tiny muted-3" style="line-height:1.6; word-break:break-word;">{{ $log->user_agent }}</p>
        @endif
    </x-ui.card>
</div>
@endsection
