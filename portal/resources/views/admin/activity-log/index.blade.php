@extends('layouts.gmu')

@section('title', 'Audit log')
@section('crumb', 'Administration')

@section('content')
<x-ui.page-head
    title="Audit log"
    :lede="number_format($logs->total()) . ' recorded ' . Str::plural('change', $logs->total()) . ' · who changed what, when, and from where'" />

<div class="stack">

    <x-ui.card title="Filter">
        <form method="GET" class="form-grid">
            <x-ui.field label="Person" name="user_id">
                <select name="user_id" class="select">
                    <option value="">Anyone</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Record type" name="model_type">
                <select name="model_type" class="select">
                    <option value="">Anything</option>
                    @foreach($modelTypes as $type)
                        <option value="{{ $type }}" @selected(request('model_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="Action" name="action">
                <select name="action" class="select">
                    <option value="">Any action</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ ucfirst($action) }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field label="From" name="date_from">
                <input type="date" name="date_from" class="input" value="{{ request('date_from') }}">
            </x-ui.field>

            <x-ui.field label="To" name="date_to">
                <input type="date" name="date_to" class="input" value="{{ request('date_to') }}">
            </x-ui.field>

            <x-ui.field label="Search" name="search">
                <input type="search" name="search" class="input" value="{{ request('search') }}" placeholder="Record or description">
            </x-ui.field>

            <div class="field wide">
                <div class="form-actions">
                    <button class="btn btn-primary">Apply filters</button>
                    <a href="{{ route('admin.activity-log.index') }}" class="btn">Clear</a>
                </div>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :flush="true">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>When</th><th>Who</th><th>Action</th><th>Record</th><th>From</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="small">
                                {{ $log->created_at?->format('d M Y H:i') }}
                                <div class="tiny muted-3">{{ $log->created_at?->diffForHumans() }}</div>
                            </td>
                            <td class="small">{{ $log->user?->name ?? 'System' }}</td>
                            <td>
                                <x-ui.pill :tone="match($log->action) {
                                    'created' => 'ok', 'deleted' => 'danger', 'restored' => 'info', default => null }">
                                    {{ ucfirst($log->action) }}
                                </x-ui.pill>
                            </td>
                            <td>
                                <span class="strong">{{ Str::limit($log->model_label ?? class_basename($log->model_type), 44) }}</span>
                                <div class="tiny muted-3">{{ class_basename($log->model_type) }} #{{ $log->model_id }}</div>
                            </td>
                            <td class="tiny mono muted-3">{{ $log->ip_address }}</td>
                            <td class="num">
                                <a href="{{ route('admin.activity-log.show', $log) }}" class="btn btn-sm">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-ui.empty icon="fa-history" title="No entries match">
                            Every create, update and delete on a grant record is logged here with the field-level
                            before and after.
                        </x-ui.empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div class="pagination-wrap">{{ $logs->links() }}</div>
</div>
@endsection
