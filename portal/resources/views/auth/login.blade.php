@extends('layouts.auth')

@section('title', 'Sign in')
@section('bare', true)

@php
    // NREIMS single sign-on is the front door once it is configured. The local
    // email-and-password form is then the administrator fallback, shown on request
    // or whenever it has just been used and failed.
    $sso       = app(\App\Services\Auth\NreimsOAuth::class)->enabled();
    $showLocal = ! $sso || request()->boolean('admin') || $errors->any() || old('email');
@endphp

@section('content')
    @if(session('status'))
        <p class="note" style="margin-bottom:16px;">{{ session('status') }}</p>
    @endif

    @if(session('sso_error'))
        <p class="note is-error" role="alert" style="margin-bottom:16px;">{{ session('sso_error') }}</p>
    @endif

    @if($errors->any())
        <p class="note is-error" role="alert" style="margin-bottom:16px;">{{ $errors->first() }}</p>
    @endif

    @if($showLocal)
        <form method="POST" action="{{ route('login') }}" style="display:flex; flex-direction:column; gap:12px;">
            @csrf
            <label for="email" class="sr-only">Email</label>
            <input id="email" type="email" name="email" class="input-light" value="{{ old('email') }}" placeholder="Email"
                   required autofocus autocomplete="username">
            <label for="password" class="sr-only">Password</label>
            <input id="password" type="password" name="password" class="input-light" placeholder="Password"
                   required autocomplete="current-password">
            <input type="hidden" name="remember" value="0">
            <button type="submit" class="btn {{ $sso ? 'btn-dark' : 'btn-light' }}" style="margin-top:4px;">Sign in</button>
        </form>

        <div style="display:flex; justify-content:center; gap:16px; margin-top:8px; flex-wrap:wrap;">
            @if($sso)
                <a href="{{ route('login') }}" class="link-light">Back</a>
            @endif
            @if(Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="link-light">Forgotten password?</a>
            @endif
        </div>
    @else
        <a href="{{ route('nreims.redirect') }}" class="btn btn-light">Sign in with NREIMS</a>
        <div style="text-align:center; margin-top:16px;">
            <a href="{{ route('login', ['admin' => 1]) }}" class="link-light">Administrator sign-in</a>
        </div>
    @endif
@endsection
