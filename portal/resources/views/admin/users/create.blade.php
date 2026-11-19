@extends('layouts.gmu')

@section('title', $sso ? 'Add a person' : 'Create an account')
@section('crumb', 'Administration')

@section('content')
<x-ui.page-head
    :title="$sso ? 'Add a person' : 'Create an account'"
    :lede="$sso
        ? 'Add someone before they have signed in, for example to make them a reviewer. Use the email they have in NREIMS: their account links the first time they sign in with it.'
        : 'The person is held at their profile page until they replace the password you set here.'" />

<form method="POST" action="{{ route('admin.users.store') }}">
    @csrf

    <div class="grid grid-wide">
        <x-ui.card title="The person">
            <div class="form-grid">
                <x-ui.field label="Full name" name="name" required wide>
                    <input name="name" class="input" value="{{ old('name') }}" required maxlength="255">
                </x-ui.field>

                <x-ui.field label="Email" name="email" required
                            :help="$sso ? 'The address on their NREIMS account' : 'This is what they sign in with'">
                    <input type="email" name="email" class="input" value="{{ old('email') }}" required>
                </x-ui.field>

                <x-ui.field label="Role" name="role_id" required
                            help="An account with no role can see nothing at all">
                    <select name="role_id" class="select" required>
                        <option value="">Choose a role…</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->display_name }}</option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field label="Department" name="department">
                    <input name="department" class="input" value="{{ old('department') }}">
                </x-ui.field>

                <x-ui.field label="Research centre" name="research_center">
                    <input name="research_center" class="input" value="{{ old('research_center') }}">
                </x-ui.field>

                <x-ui.field label="Phone" name="phone" help="Used for SMS deadline alerts, when a gateway is configured">
                    <input name="phone" class="input" value="{{ old('phone') }}" placeholder="0754 000 000">
                </x-ui.field>
            </div>
        </x-ui.card>

        <div class="stack">
            @if($sso)
                <x-ui.card title="Signing in">
                    <p class="small muted" style="line-height:1.7;">
                        They sign in with NREIMS. No password is needed here.
                    </p>
                    <details style="margin-top:12px;" @if($errors->has('password')) open @endif>
                        <summary class="small strong" style="cursor:pointer;">Give a local password instead</summary>
                        <p class="small muted" style="margin:8px 0 12px; line-height:1.7;">
                            Only for a fallback administrator who must get in when NREIMS is down.
                        </p>
                        <div class="stack-sm">
                            <x-ui.field label="Password" name="password" help="At least 8 characters">
                                <input type="password" name="password" class="input" minlength="8" autocomplete="new-password">
                            </x-ui.field>
                            <x-ui.field label="Confirm password" name="password_confirmation">
                                <input type="password" name="password_confirmation" class="input" minlength="8" autocomplete="new-password">
                            </x-ui.field>
                        </div>
                    </details>
                </x-ui.card>
            @else
                <x-ui.card title="First password">
                    <div class="stack-sm">
                        <x-ui.field label="Password" name="password" required help="At least 8 characters">
                            <input type="password" name="password" class="input" required minlength="8" autocomplete="new-password">
                        </x-ui.field>

                        <x-ui.field label="Confirm password" name="password_confirmation" required>
                            <input type="password" name="password_confirmation" class="input" required minlength="8" autocomplete="new-password">
                        </x-ui.field>
                    </div>
                    <p class="small muted" style="margin-top:12px; line-height:1.7;">
                        Hand this over in person or by a channel you trust, not by email alongside the address. The account
                        cannot be used for anything else until they set their own.
                    </p>
                </x-ui.card>
            @endif

            <x-ui.card title="Access">
                <label class="check">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                    <span>Account is active</span>
                </label>
            </x-ui.card>

            <x-ui.card>
                <div class="form-actions">
                    <button class="btn btn-primary">{{ $sso ? 'Add person' : 'Create account' }}</button>
                    <a href="{{ route('admin.users.index') }}" class="btn">Cancel</a>
                </div>
            </x-ui.card>
        </div>
    </div>
</form>
@endsection
