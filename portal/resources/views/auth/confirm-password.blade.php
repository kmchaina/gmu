@extends('layouts.auth')

@section('title', 'Confirm your password')

@section('content')
<h2 style="font-size:23px; letter-spacing:-.5px;">Confirm your password</h2>
<p class="small muted" style="margin-top:8px;">
    This part of the portal asks you to confirm who you are before continuing.
</p>

<form method="POST" action="{{ route('password.confirm') }}" style="margin-top:24px;">
    @csrf
    <x-ui.field label="Password" name="password" required>
        <input type="password" name="password" class="input" required autofocus autocomplete="current-password">
    </x-ui.field>
    <div class="form-actions" style="margin-top:14px;">
        <button class="btn btn-primary btn-block" style="padding:11px;">Confirm</button>
    </div>
</form>
@endsection
