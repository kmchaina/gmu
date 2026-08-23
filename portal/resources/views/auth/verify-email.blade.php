@extends('layouts.auth')

@section('title', 'Verify your email')

@section('content')
<h2 style="font-size:23px; letter-spacing:-.5px;">Verify your email</h2>
<p class="small muted" style="margin-top:8px; line-height:1.7;">
    We have sent a link to your address. Open it to confirm the account. If it has not arrived, we can send another.
</p>

@if(session('status') === 'verification-link-sent')
    <div class="alert alert-success" style="margin-top:20px;">
        <i class="fas fa-check-circle"></i><span>A new link is on its way.</span>
    </div>
@endif

<div class="row" style="gap:10px; margin-top:24px;">
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="btn btn-primary">Send it again</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn">Sign out</button>
    </form>
</div>
@endsection
