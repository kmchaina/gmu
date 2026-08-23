@extends('layouts.auth')

@section('title', 'Choose a new password')

@section('content')
<h2 style="font-size:23px; letter-spacing:-.5px;">Choose a new password</h2>
<p class="small muted" style="margin-top:8px;">Use something you do not use anywhere else.</p>

<form method="POST" action="{{ route('password.store') }}" style="margin-top:24px;">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div class="stack-sm">
        <x-ui.field label="Email" name="email" required>
            <input type="email" name="email" class="input" value="{{ old('email', $request->email) }}" required autofocus>
        </x-ui.field>

        <x-ui.field label="New password" name="password" required>
            <input type="password" name="password" class="input" required autocomplete="new-password">
        </x-ui.field>

        <x-ui.field label="Confirm password" name="password_confirmation" required>
            <input type="password" name="password_confirmation" class="input" required autocomplete="new-password">
        </x-ui.field>

        <button class="btn btn-primary btn-block" style="margin-top:10px; padding:11px;">Set the password</button>
    </div>
</form>
@endsection
