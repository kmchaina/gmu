@extends('layouts.gmu')

@section('title', $user->name)
@section('crumb', 'Administration')

@section('content')
@php
    $initials = collect(explode(' ', $user->name))->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');
@endphp

<div class="page-head">
    <div class="row" style="gap:18px; min-width:0;">
        <div class="avatar" style="width:54px; height:54px; flex:0 0 54px; font-size:18px;">{{ strtoupper($initials) }}</div>
        <div style="min-width:0;">
            <a href="{{ route('admin.users.index') }}" class="small">&larr; User management</a>
            <h1 style="margin-top:6px;">{{ $user->name }}</h1>
            <div class="row" style="gap:12px; margin-top:8px;">
                <x-ui.pill :tone="$user->role?->name === 'coordinator' ? 'navy' : null">
                    {{ $user->role?->display_name ?? 'No role' }}
                </x-ui.pill>
                <x-ui.pill :tone="$user->is_active ? 'ok' : 'danger'">{{ $user->is_active ? 'Active' : 'Deactivated' }}</x-ui.pill>
            </div>
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('researchers.show', $user) }}" class="btn">Researcher profile</a>
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary"><i class="fas fa-pen"></i> Edit account</a>
    </div>
</div>

<div class="grid grid-wide">
    <x-ui.card title="Account details">
        <div class="stack-sm small">
            <div class="row-between"><span class="muted">Email</span><span>{{ $user->email }}</span></div>
            <div class="row-between"><span class="muted">Phone</span><span>{{ $user->phone ?? '—' }}</span></div>
            <div class="row-between"><span class="muted">Department</span><span>{{ $user->department ?? '—' }}</span></div>
            <div class="row-between"><span class="muted">Research centre</span><span>{{ $user->research_center ?? '—' }}</span></div>
            <div class="row-between"><span class="muted">Created</span><span>{{ $user->created_at?->format('d M Y') }}</span></div>
            <div class="row-between"><span class="muted">Email verified</span>
                <span>{{ $user->email_verified_at ? $user->email_verified_at->format('d M Y') : 'Not verified' }}</span></div>
            <div class="row-between"><span class="muted">Password</span>
                <span class="{{ $user->must_change_password ? 'text-warn' : '' }}">
                    {{ $user->must_change_password ? 'Must be changed at next sign-in' : 'Set by the user' }}
                </span></div>
        </div>
    </x-ui.card>

    <x-ui.card title="What this role can do">
        @php
            $abilities = match($user->role?->name) {
                'coordinator' => ['Everything in the portal', 'Create and deactivate accounts', 'Clear conflict-of-interest disclosures', 'Approve library content', 'Sign off closeout'],
                'planning_officer' => ['Post and manage funding calls', 'Select teams from expressions of interest', 'Maintain the compliance calendar', 'Log monitoring visits'],
                'finance_officer' => ['Budget review track on proposals', 'Budget lines and the expenditure ledger', 'Verify reports (never their own)', 'Closeout reconciliation'],
                'legal_officer' => ['Compliance review track on proposals', 'Award setup and contract records', 'Sub-award agreements', 'Ethics records'],
                'research_scientist' => ['Technical review track on proposals', 'Lead or join proposals', 'Monitoring visits'],
                'drcp' => ['Institutional clearance of proposals after internal review'],
                'dg' => ['Final institutional approval and the Letter of Support'],
                'pi' => ['Lead proposals and see their own grants', 'Submit reports and deliverables', 'Express interest in calls'],
                'secretary' => ['Administrative support', 'Compliance calendar entries'],
                default => ['Nothing — an account without a role cannot see grant data'],
            };
        @endphp
        <ul class="small muted" style="margin:0; padding-left:18px; line-height:1.9;">
            @foreach($abilities as $ability)<li>{{ $ability }}</li>@endforeach
        </ul>
    </x-ui.card>
</div>
@endsection
