@extends('layouts.gmu')

@section('title', 'Edit account')
@section('crumb', 'Administration')

@section('content')
<x-ui.page-head title="Edit account" :lede="$user->name . ' · ' . $user->email">
    <x-slot:actions>
        <a href="{{ route('admin.users.show', $user) }}" class="btn">View account</a>
    </x-slot:actions>
</x-ui.page-head>

<form method="POST" action="{{ route('admin.users.update', $user) }}">
    @csrf @method('PUT')

    <div class="grid grid-wide">
        <x-ui.card title="The person">
            <div class="form-grid">
                <x-ui.field label="Full name" name="name" required wide>
                    <input name="name" class="input" value="{{ old('name', $user->name) }}" required maxlength="255">
                </x-ui.field>

                <x-ui.field label="Email" name="email" required>
                    <input type="email" name="email" class="input" value="{{ old('email', $user->email) }}" required>
                </x-ui.field>

                <x-ui.field label="Role" name="role_id" required>
                    <select name="role_id" class="select" required>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->display_name }}</option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field label="Department" name="department">
                    <input name="department" class="input" value="{{ old('department', $user->department) }}">
                </x-ui.field>

                <x-ui.field label="Research centre" name="research_center">
                    <input name="research_center" class="input" value="{{ old('research_center', $user->research_center) }}">
                </x-ui.field>

                <x-ui.field label="Phone" name="phone">
                    <input name="phone" class="input" value="{{ old('phone', $user->phone) }}">
                </x-ui.field>
            </div>
        </x-ui.card>

        <div class="stack">
            <x-ui.card title="Reset the password">
                <div class="stack-sm">
                    <x-ui.field label="New password" name="password" help="Leave blank to keep the current one">
                        <input type="password" name="password" class="input" minlength="8">
                    </x-ui.field>

                    <x-ui.field label="Confirm" name="password_confirmation">
                        <input type="password" name="password_confirmation" class="input" minlength="8">
                    </x-ui.field>
                </div>
                <p class="small muted" style="margin-top:12px; line-height:1.7;">
                    Setting a password here holds the account at the profile page until the person chooses their own.
                </p>
            </x-ui.card>

            <x-ui.card title="Access" :accent="$user->is_active ? null : 'danger'">
                <label class="check">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))>
                    <span>Account is active</span>
                </label>
                <p class="small muted" style="margin-top:12px; line-height:1.7;">
                    Deactivating takes effect immediately: the person cannot sign in, and any open session ends on
                    their next click. Their records stay exactly as they are.
                </p>
            </x-ui.card>

            <x-ui.card>
                <div class="form-actions">
                    <button class="btn btn-primary">Save changes</button>
                    <a href="{{ route('admin.users.index') }}" class="btn">Cancel</a>
                </div>
            </x-ui.card>

            @if($user->id !== auth()->id())
                <x-ui.card title="Deactivate this account" accent="danger">
                    <p class="small muted" style="line-height:1.7;">
                        Accounts are never deleted — grant records have to outlive the people who created them.
                        This deactivates instead.
                    </p>
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                          onsubmit="return confirm('Deactivate this account?')" style="margin-top:12px;">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-block">Deactivate account</button>
                    </form>
                </x-ui.card>
            @endif
        </div>
    </div>
</form>
@endsection
