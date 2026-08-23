<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · NIMR GMU</title>

    {{-- Everything is served from this application: the portal has to render
         correctly on a network with no route to the internet. --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/gmu.css') }}?v={{ filemtime(public_path('css/gmu.css')) }}">
    @stack('head')
</head>
<body>
@php
    $user     = auth()->user();
    $unread   = $user->unreadNotificationsCount();
    $initials = collect(explode(' ', $user->name))->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');
@endphp

<div class="shell">

    <aside class="sidebar">
        <div class="sidebar-head">
            <div class="sidebar-mark"><i class="fas fa-university"></i></div>
            <div>
                <span class="sidebar-name">NIMR GMU</span>
                <span class="sidebar-sub">Grants Management Unit</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-group">Main</div>
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            @can('view-analytics')
                <a href="{{ route('analytics') }}" class="nav-link {{ request()->routeIs('analytics') ? 'is-active' : '' }}">
                    <i class="fas fa-chart-line"></i> Analytics
                </a>
            @endcan

            <div class="nav-group">Grant opportunities</div>
            <a href="{{ route('calls.index') }}" class="nav-link {{ request()->routeIs('calls.*') ? 'is-active' : '' }}">
                <i class="fas fa-bullhorn"></i> Funding Calls
            </a>
            @if($user->hasAnyRole(['coordinator', 'planning_officer']))
                <a href="{{ route('donors.index') }}" class="nav-link {{ request()->routeIs('donors.*') ? 'is-active' : '' }}">
                    <i class="fas fa-handshake"></i> Donors
                </a>
            @endif

            <div class="nav-group">Proposals</div>
            <a href="{{ route('proposals.index') }}" class="nav-link {{ request()->routeIs('proposals.*') ? 'is-active' : '' }}">
                <i class="fas fa-file-alt"></i> Proposals
            </a>
            <a href="{{ route('library.index') }}" class="nav-link {{ request()->routeIs('library.*') ? 'is-active' : '' }}">
                <i class="fas fa-book-open"></i> Proposal Library
            </a>
            <a href="{{ route('researchers.index') }}" class="nav-link {{ request()->routeIs('researchers.*') ? 'is-active' : '' }}">
                <i class="fas fa-user-graduate"></i> {{ $user->isGmuStaff() ? 'Researchers' : 'My Profile' }}
            </a>

            <div class="nav-group">Awards &amp; compliance</div>
            <a href="{{ route('awards.index') }}" class="nav-link {{ request()->routeIs('awards.*') ? 'is-active' : '' }}">
                <i class="fas fa-trophy"></i> Active Awards
            </a>
            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}">
                <i class="fas fa-file-invoice"></i> Reports
            </a>

            <div class="nav-group">Library</div>
            <a href="{{ route('documents.index') }}" class="nav-link {{ request()->routeIs('documents.*') ? 'is-active' : '' }}">
                <i class="fas fa-folder-open"></i> Document Repository
            </a>
            <a href="{{ route('donor-policies.index') }}" class="nav-link {{ request()->routeIs('donor-policies.*') ? 'is-active' : '' }}">
                <i class="fas fa-book"></i> Donor Policies
            </a>

            @can('view-analytics')
                <div class="nav-group">Intelligence</div>
                <a href="{{ route('ai.hub') }}" class="nav-link {{ request()->routeIs('ai.*') ? 'is-active' : '' }}">
                    <i class="fas fa-robot"></i> AI Intelligence Hub
                </a>
            @endcan

            @if($user->isCoordinator())
                <div class="nav-group">Administration</div>
                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">
                    <i class="fas fa-users-cog"></i> User Management
                </a>
                <a href="{{ route('admin.activity-log.index') }}" class="nav-link {{ request()->routeIs('admin.activity-log.*') ? 'is-active' : '' }}">
                    <i class="fas fa-history"></i> Audit Log
                </a>
            @endif

            <div class="nav-group">Account</div>
            <a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'is-active' : '' }}">
                <i class="fas fa-bell"></i> Notifications
                @if($unread > 0)<span class="nav-count">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
            </a>
            <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}">
                <i class="fas fa-user-cog"></i> My Account
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link" style="width:100%; border:0; background:none; cursor:pointer; text-align:left;">
                    <i class="fas fa-sign-out-alt"></i> Sign out
                </button>
            </form>
        </nav>

        <div class="sidebar-foot">
            <span>National Institute for Medical Research</span>
            <span class="mono">Reporting currency {{ \App\Support\Money::base() }}</span>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div>
                <div class="topbar-crumb">@yield('crumb', 'Grants Management Unit')</div>
                <div class="topbar-title">@yield('title', 'Dashboard')</div>
            </div>

            <div class="topbar-right">
                <form class="topbar-search" method="GET" action="{{ route('awards.index') }}">
                    <i class="fas fa-search"></i>
                    <input type="search" name="search" placeholder="Search awards" value="{{ request('search') }}"
                           aria-label="Search awards">
                </form>

                <a href="{{ route('notifications.index') }}" class="bell" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    @if($unread > 0)<span class="bell-count">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
                </a>

                <div class="topbar-divider"></div>

                <a href="{{ route('profile.edit') }}" class="row" style="gap:10px; color:inherit; text-decoration:none;">
                    <div class="avatar">{{ strtoupper($initials) }}</div>
                    <div>
                        <div class="user-name">{{ $user->name }}</div>
                        <div class="user-role">{{ $user->role?->display_name ?? 'No role assigned' }}</div>
                    </div>
                </a>
            </div>
        </header>

        <main class="page">
            @if(session('success'))
                <div class="alert alert-success"><i class="fas fa-check-circle"></i><span>{{ session('success') }}</span></div>
            @endif
            @if(session('error'))
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><span>{{ session('error') }}</span></div>
            @endif
            @if(session('status'))
                <div class="alert alert-info"><i class="fas fa-info-circle"></i><span>{{ session('status') }}</span></div>
            @endif
            @if($errors->any() && ! $errors->has('__hidden'))
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>{{ $errors->count() }} {{ Str::plural('problem', $errors->count()) }} with what was submitted</strong>
                        <ul style="margin:6px 0 0; padding-left:18px;">
                            @foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
