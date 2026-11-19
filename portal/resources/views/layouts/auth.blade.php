<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sign in') · NIMR Grants</title>
    <link rel="icon" type="image/png" href="{{ asset('images/nimr-logo.png') }}">

    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/gmu.css') }}?v={{ filemtime(public_path('css/gmu.css')) }}">

    <style>
        body { background:var(--brand); }
        .auth { min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center;
            padding:48px 24px; color:#fff; }
        .auth-col { width:100%; max-width:380px; display:flex; flex-direction:column; align-items:center; text-align:center; }
        .auth-logo { width:112px; height:112px; object-fit:contain; border-radius:50%; background:#fff; padding:4px; }
        .auth-title { margin-top:24px; font-size:28px; letter-spacing:-0.02em; }
        .auth-sub { margin-top:6px; font-size:15px; color:var(--blue-50); }
        .auth-body { width:100%; margin-top:40px; text-align:left; }
        .auth-foot { margin-top:64px; font-size:12px; color:var(--blue-50); }

        /* Buttons and fields that sit straight on the blue */
        .auth .btn-light { width:100%; min-height:52px; border:0; border-radius:12px; background:#fff; color:var(--blue-700);
            font-size:15px; font-weight:600; }
        .auth .btn-light:hover { background:var(--blue-50); color:var(--blue-800); }
        .auth .btn-dark { width:100%; min-height:52px; border:0; border-radius:12px; background:var(--panel); color:#fff;
            font-size:15px; font-weight:600; }
        .auth .btn-dark:hover { background:var(--panel-hover); color:#fff; }
        .auth .input-light { width:100%; min-height:50px; padding:10px 14px; border:1px solid transparent; border-radius:12px;
            background:#fff; color:var(--ink); font-size:15px; }
        .auth .input-light::placeholder { color:var(--muted-2); }
        .auth .input-light:focus { outline:3px solid var(--blue-200); outline-offset:0; }
        .auth .link-light { display:inline-flex; align-items:center; min-height:44px; padding:0 4px; background:none; border:0;
            color:var(--blue-50); font-size:14px; font-weight:500; cursor:pointer; }
        .auth .link-light:hover { color:#fff; text-decoration:underline; }
        .auth .note { padding:12px 14px; border-radius:12px; background:rgba(14,26,58,.35); color:#fff; font-size:14px; }
        .auth .note.is-error { background:#FDECEC; color:var(--danger-ink); }

        /* Pages other than sign-in keep their forms on a white card */
        .auth-card { background:#fff; color:var(--ink); border-radius:16px; padding:28px; box-shadow:var(--shadow-lg); }
    </style>
</head>
<body>
<div class="auth">
    <div class="auth-col">
        <img src="{{ asset('images/nimr-logo.png') }}" alt="National Institute for Medical Research logo" class="auth-logo">
        <h1 class="auth-title">NIMR Grants</h1>
        <p class="auth-sub">Grants Management Unit</p>

        <div class="auth-body">
            @hasSection('bare')
                @yield('content')
            @else
                <div class="auth-card">@yield('content')</div>
            @endif
        </div>
    </div>

    <p class="auth-foot">National Institute for Medical Research</p>
</div>
</body>
</html>
