@extends('layouts.gmu')

@section('title', 'My account')
@section('crumb', 'Account')

@section('content')
@php $user = auth()->user(); @endphp

<x-ui.page-head
    title="My account"
    :lede="$user->email . ' · ' . ($user->role?->display_name ?? 'No role assigned')" />

@if($user->must_change_password)
    <div class="alert alert-warn">
        <i class="fas fa-key"></i>
        <div>
            <strong>Choose your own password to continue.</strong>
            The password on this account was set by somebody else. Until you replace it below, the rest of the portal
            is held.
        </div>
    </div>
@endif

<div class="grid grid-wide">
    <div class="stack">

        <x-ui.card title="Your details" note="Name and email as they appear across the portal">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf @method('PATCH')

                <div class="form-grid">
                    <x-ui.field label="Full name" name="name" required>
                        <input name="name" class="input" value="{{ old('name', $user->name) }}" required maxlength="255">
                    </x-ui.field>

                    <x-ui.field label="Email" name="email" required>
                        <input type="email" name="email" class="input" value="{{ old('email', $user->email) }}" required>
                    </x-ui.field>
                </div>

                @if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <p class="small text-warn" style="margin-top:12px;">
                        Your email address is not verified.
                        <button form="send-verification" class="btn btn-sm" style="margin-left:6px;">Resend verification</button>
                    </p>
                @endif

                <div class="form-actions">
                    <button class="btn btn-primary">Save details</button>
                    @if(session('status') === 'profile-updated')
                        <span class="small text-ok"><i class="fas fa-check"></i> Saved</span>
                    @endif
                </div>
            </form>
        </x-ui.card>

        <x-ui.card title="Password" note="Use something you do not use anywhere else">
            <form method="POST" action="{{ route('password.update') }}">
                @csrf @method('PUT')

                <div class="form-grid">
                    <x-ui.field label="Current password" name="current_password" required wide>
                        <input type="password" name="current_password" class="input" required autocomplete="current-password">
                    </x-ui.field>

                    <x-ui.field label="New password" name="password" required>
                        <input type="password" name="password" class="input" required autocomplete="new-password">
                    </x-ui.field>

                    <x-ui.field label="Confirm new password" name="password_confirmation" required>
                        <input type="password" name="password_confirmation" class="input" required autocomplete="new-password">
                    </x-ui.field>
                </div>

                <div class="form-actions">
                    <button class="btn btn-primary">Change password</button>
                    @if(session('status') === 'password-updated')
                        <span class="small text-ok"><i class="fas fa-check"></i> Password changed</span>
                    @endif
                </div>
            </form>
        </x-ui.card>
    </div>

    <div class="stack">
        <x-ui.card title="Your access">
            <div class="stack-sm small">
                <div class="row-between"><span class="muted">Role</span>
                    <span>{{ $user->role?->display_name ?? 'None' }}</span></div>
                <div class="row-between"><span class="muted">Department</span><span>{{ $user->department ?? '—' }}</span></div>
                <div class="row-between"><span class="muted">Research centre</span><span>{{ $user->research_center ?? '—' }}</span></div>
                <div class="row-between"><span class="muted">Phone</span><span>{{ $user->phone ?? '—' }}</span></div>
                <div class="row-between"><span class="muted">Member since</span><span>{{ $user->created_at?->format('M Y') }}</span></div>
            </div>
            <p class="small muted-3" style="margin-top:14px; line-height:1.7;">
                Your role decides what you can see and do. Only the GMU Coordinator can change it.
            </p>
            @if($user->isResearcher())
                <a href="{{ route('researchers.show', $user) }}" class="btn btn-block" style="margin-top:12px;">
                    <i class="fas fa-user-graduate"></i> My researcher profile
                </a>
            @endif
        </x-ui.card>

        <x-ui.card title="Close my account" accent="danger">
            <p class="small muted" style="line-height:1.7;">
                Closing signs you out and removes your access immediately. Your grant records — proposals, awards,
                reports and documents — are institutional records and are kept. Only the Coordinator can reopen a
                closed account.
            </p>

            <form method="POST" action="{{ route('profile.destroy') }}" style="margin-top:14px;"
                  onsubmit="return confirm('Close your account? You will be signed out and will not be able to sign back in.')">
                @csrf @method('DELETE')

                <x-ui.field label="Confirm with your password" name="password">
                    <input type="password" name="password" class="input" required autocomplete="current-password">
                    @error('password', 'userDeletion')<span class="error">{{ $message }}</span>@enderror
                </x-ui.field>

                <div class="form-actions"><button class="btn btn-danger btn-block">Close my account</button></div>
            </form>
        </x-ui.card>
    </div>
</div>

@if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail)
    <form id="send-verification" method="POST" action="{{ route('verification.send') }}">@csrf</form>
@endif
@endsection
