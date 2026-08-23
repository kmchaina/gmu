@extends('layouts.auth')

@section('title', 'Reset your password')

@section('content')
<h2 style="font-size:23px; letter-spacing:-.5px;">Reset your password</h2>
<p class="small muted" style="margin-top:8px;">
    Give the email address on your account and we will send a link to set a new password.
</p>

@if(session('status'))
    <div class="alert alert-success" style="margin-top:20px;">
        <i class="fas fa-check-circle"></i><span>{{ session('status') }}</span>
    </div>
@endif

<form method="POST" action="{{ route('password.email') }}" style="margin-top:24px;">
    @csrf
    <x-ui.field label="Email" name="email" required>
        <input type="email" name="email" class="input" value="{{ old('email') }}" required autofocus>
    </x-ui.field>
    <div class="form-actions" style="margin-top:14px;">
        <button class="btn btn-primary btn-block" style="padding:11px;">Send the link</button>
    </div>
</form>

<p class="small" style="margin-top:22px;"><a href="{{ route('login') }}">&larr; Back to sign in</a></p>
@endsection
