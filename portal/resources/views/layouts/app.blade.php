<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NIMR GMU') - Grants Management Unit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, sans-serif; background: #f4f6f9; margin: 0; }
        .sidebar { width: 260px; min-height: 100vh; background: #1a2942; position: fixed; left: 0; top: 0; z-index: 100; overflow-y: auto; }
        .sidebar-logo { padding: 20px 20px 14px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-logo h4 { color: #fff; font-size: 15px; font-weight: 700; margin: 0 0 2px; }
        .sidebar-logo small { color: rgba(255,255,255,0.45); font-size: 11px; }
        .sidebar-menu { padding: 8px 0 30px; }
        .menu-section { padding: 14px 20px 4px; font-size: 10px; font-weight: 700; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1.2px; }
        .menu-item a { display: flex; align-items: center; gap: 11px; padding: 9px 20px; color: rgba(255,255,255,0.65); text-decoration: none; font-size: 13px; transition: all 0.2s; border-left: 3px solid transparent; }
        .menu-item a:hover { background: rgba(255,255,255,0.06); color: #fff; }
        .menu-item a.active { background: rgba(78,154,241,0.15); color: #fff; border-left-color: #4e9af1; }
        .menu-item a i { width: 18px; text-align: center; font-size: 13px; opacity: 0.85; }
        .main-content { margin-left: 260px; min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { background: #fff; border-bottom: 1px solid #e8ecf0; padding: 0 25px; height: 60px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .topbar-title { font-size: 15px; font-weight: 600; color: #1a2942; }
        .topbar-right { display: flex; align-items: center; gap: 18px; }
        .notif-bell { position: relative; cursor: pointer; color: #555; text-decoration: none; }
        .notif-badge { position: absolute; top: -5px; right: -6px; background: #e74c3c; color: #fff; border-radius: 50%; width: 16px; height: 16px; font-size: 9px; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .user-pill { display: flex; align-items: center; gap: 9px; }
        .user-avatar { width: 34px; height: 34px; border-radius: 50%; background: #4e9af1; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0; }
        .user-info small { display: block; font-size: 10px; color: #999; line-height: 1.2; }
        .user-info span { font-size: 13px; font-weight: 600; color: #1a2942; line-height: 1.4; }
        .page-content { padding: 25px; flex: 1; }
        .alert-success { background: #d4edda; border: 1px solid #b8ddc4; color: #155724; padding: 11px 16px; border-radius: 6px; margin-bottom: 18px; font-size: 13.5px; }
        .alert-danger  { background: #f8d7da; border: 1px solid #f1b2b8; color: #721c24; padding: 11px 16px; border-radius: 6px; margin-bottom: 18px; font-size: 13.5px; }
        .badge-status { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-open    { background: #d4edda; color: #155724; }
        .badge-closed  { background: #f8d7da; color: #721c24; }
        .badge-draft   { background: #fff3cd; color: #856404; }
        .badge-active  { background: #cce5ff; color: #004085; }
        .badge-awarded { background: #d4edda; color: #155724; }
        .badge-declined{ background: #f8d7da; color: #721c24; }
        .badge-submitted{ background: #e2e3e5; color: #383d41; }
        .traffic-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; }
        .traffic-green  { background: #27ae60; }
        .traffic-yellow { background: #f39c12; }
        .traffic-red    { background: #e74c3c; }
        .logout-btn { background: none; border: none; width: 100%; padding: 0; cursor: pointer; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <h4><i class="fas fa-university"></i> &nbsp;NIMR GMU</h4>
        <small>Grants Management Unit</small>
    </div>

    <nav class="sidebar-menu">
        <div class="menu-section">Main</div>
        <div class="menu-item">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </div>

        <div class="menu-section">Grant Opportunities</div>
        <div class="menu-item">
            <a href="{{ route('calls.index') }}" class="{{ request()->routeIs('calls.*') ? 'active' : '' }}">
                <i class="fas fa-bullhorn"></i> Funding Calls
            </a>
        </div>
        @if(auth()->user()->hasAnyRole(['coordinator','planning_officer']))
        <div class="menu-item">
            <a href="{{ route('donors.index') }}" class="{{ request()->routeIs('donors.*') ? 'active' : '' }}">
                <i class="fas fa-handshake"></i> Donors
            </a>
        </div>
        @endif

        <div class="menu-section">Proposals</div>
        <div class="menu-item">
            <a href="{{ route('proposals.index') }}" class="{{ request()->routeIs('proposals.*') ? 'active' : '' }}">
                <i class="fas fa-file-alt"></i> Proposal Pipeline
            </a>
        </div>

        <div class="menu-section">Awards & Compliance</div>
        <div class="menu-item">
            <a href="{{ route('awards.index') }}" class="{{ request()->routeIs('awards.*') ? 'active' : '' }}">
                <i class="fas fa-trophy"></i> Active Awards
            </a>
        </div>
        <div class="menu-item">
            <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
        </div>

        <div class="menu-section">Library</div>
        <div class="menu-item">
            <a href="{{ route('documents.index') }}" class="{{ request()->routeIs('documents.*') ? 'active' : '' }}">
                <i class="fas fa-folder-open"></i> Document Repository
            </a>
        </div>
        <div class="menu-item">
            <a href="{{ route('donor-policies.index') }}" class="{{ request()->routeIs('donor-policies.*') ? 'active' : '' }}">
                <i class="fas fa-book"></i> Donor Guidelines
            </a>
        </div>

        @if(auth()->user()->isCoordinator())
        <div class="menu-section">Administration</div>
        <div class="menu-item">
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="fas fa-users-cog"></i> User Management
            </a>
        </div>
        @endif

        <div class="menu-section">Account</div>
        <div class="menu-item">
            <form method="POST" action="{{ route('logout') }}" class="logout-btn">
                @csrf
                <a href="#" onclick="this.closest('form').submit(); return false;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </form>
        </div>
    </nav>
</div>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
        <div class="topbar-right">
            <a href="{{ route('notifications.index') }}" class="notif-bell">
                <i class="fas fa-bell fa-lg"></i>
                @php $unread = auth()->user()->unreadNotificationsCount(); @endphp
                @if($unread > 0)
                    <span class="notif-badge">{{ $unread > 9 ? '9+' : $unread }}</span>
                @endif
            </a>
            <div class="user-pill">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                <div class="user-info">
                    <span>{{ auth()->user()->name }}</span>
                    <small>{{ auth()->user()->role?->display_name ?? 'No Role' }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="page-content">
        @if(session('success'))
            <div class="alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert-danger"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
        @endif

        @yield('content')
    </div>
</div>

</body>
</html>
