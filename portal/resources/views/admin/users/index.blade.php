@extends('layouts.gmu')

@section('title', 'User management')
@section('crumb', 'Administration')

@section('content')
<x-ui.page-head
    title="User management"
    :lede="$users->total() . ' ' . Str::plural('account', $users->total()) . ' · accounts are created here, never by self-registration'">
    <x-slot:actions>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create an account</a>
    </x-slot:actions>
</x-ui.page-head>

<div class="stack">

    <div class="row-between">
        <div class="chips">
            <a href="{{ route('admin.users.index') }}" class="chip {{ ! request('role') && ! request('status') ? 'is-active' : '' }}">All</a>
            @foreach($roles as $role)
                <a href="{{ route('admin.users.index', ['role' => $role->name]) }}"
                   class="chip {{ request('role') === $role->name ? 'is-active' : '' }}">{{ $role->display_name }}</a>
            @endforeach
            <a href="{{ route('admin.users.index', ['status' => 'inactive']) }}"
               class="chip {{ request('status') === 'inactive' ? 'is-active' : '' }}">Deactivated</a>
        </div>

        <form method="GET" class="row" style="gap:8px;">
            <input type="search" name="search" class="input" style="width:220px;" placeholder="Name, email or department"
                   value="{{ request('search') }}">
            <button class="btn"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <x-ui.card :flush="true">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Person</th><th>Role</th><th>Department</th><th>Status</th><th>Password</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <a href="{{ route('admin.users.show', $user) }}" class="strong">{{ $user->name }}</a>
                                <div class="tiny muted-3">{{ $user->email }}</div>
                            </td>
                            <td>
                                @if($user->role)
                                    <x-ui.pill :tone="$user->role->name === 'coordinator' ? 'navy' : null">{{ $user->role->display_name }}</x-ui.pill>
                                @else
                                    <x-ui.pill tone="danger">No role</x-ui.pill>
                                @endif
                            </td>
                            <td class="small muted">{{ $user->department ?? '—' }}</td>
                            <td>
                                <x-ui.pill :tone="$user->is_active ? 'ok' : 'danger'">
                                    {{ $user->is_active ? 'Active' : 'Deactivated' }}
                                </x-ui.pill>
                            </td>
                            <td class="small">
                                @if($user->must_change_password)
                                    <span class="text-warn">Must change at next sign-in</span>
                                @else
                                    <span class="muted-3">Set by the user</span>
                                @endif
                            </td>
                            <td class="num">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm"><i class="fas fa-pen"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-ui.empty icon="fa-users-cog">No accounts match.</x-ui.empty></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div class="pagination-wrap">{{ $users->links() }}</div>
</div>
@endsection
