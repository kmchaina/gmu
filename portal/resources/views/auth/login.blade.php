@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
<h2 style="font-size:23px; letter-spacing:-.5px;">Sign in</h2>
<p class="small muted" style="margin-top:8px;">
    Accounts are created by the GMU Coordinator. If you do not have one, ask the unit.
</p>

@if(session('status'))
    <div class="alert alert-info" style="margin-top:20px;">
        <i class="fas fa-info-circle"></i><span>{{ session('status') }}</span>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-error" style="margin-top:20px;">
        <i class="fas fa-exclamation-circle"></i>
        <div>
            @foreach($errors->all() as $message)<div>{{ $message }}</div>@endforeach
        </div>
    </div>
@endif

<form method="POST" action="{{ route('login') }}" style="margin-top:24px;">
    @csrf

    <div class="stack-sm">
        <x-ui.field label="Email" name="email" required>
            <input type="email" name="email" class="input" value="{{ old('email') }}" required autofocus autocomplete="username">
        </x-ui.field>

        <x-ui.field label="Password" name="password" required>
            <input type="password" name="password" class="input" required autocomplete="current-password">
        </x-ui.field>

        <div class="row-between" style="margin-top:4px;">
            <label class="check">
                <input type="checkbox" name="remember"> <span class="small">Keep me signed in</span>
            </label>
            @if(Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="small">Forgotten password?</a>
            @endif
        </div>

        <button class="btn btn-primary btn-block" style="margin-top:10px; padding:11px;">Sign in</button>
    </div>
</form>

<p class="tiny muted-3" style="margin-top:28px; line-height:1.7;">
    This is an institutional system. Activity is logged, including sign-in attempts.
</p>
@endsection
