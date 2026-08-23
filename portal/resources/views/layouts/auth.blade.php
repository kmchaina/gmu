<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sign in') · NIMR GMU</title>

    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/gmu.css') }}?v={{ filemtime(public_path('css/gmu.css')) }}">

    <style>
        .auth { display:flex; min-height:100vh; }
        .auth-brand {
            flex:1 1 46%; background:var(--navy-900); color:#fff; padding:56px 56px 44px;
            display:flex; flex-direction:column; justify-content:space-between;
            background-image:radial-gradient(circle at 15% 12%, rgba(0,85,165,.34), transparent 46%),
                             radial-gradient(circle at 85% 82%, rgba(0,168,107,.16), transparent 42%);
        }
        .auth-form { flex:1 1 54%; display:flex; align-items:center; justify-content:center; padding:40px 24px; }
        .auth-card { width:100%; max-width:404px; }
        .auth-mark { width:44px; height:44px; border-radius:12px; background:linear-gradient(145deg,var(--navy-500),var(--navy-700));
            display:flex; align-items:center; justify-content:center; font-size:17px; }
        .auth-points { display:flex; flex-direction:column; gap:16px; margin-top:34px; }
        .auth-point { display:flex; gap:13px; align-items:flex-start; }
        .auth-point i { color:var(--green); margin-top:3px; font-size:12px; }
        .auth-point span { color:rgba(255,255,255,.68); font-size:13.5px; line-height:1.6; }
        @media (max-width:900px) { .auth-brand { display:none; } }
    </style>
</head>
<body>
<div class="auth">
    <aside class="auth-brand">
        <div>
            <div class="row" style="gap:14px;">
                <div class="auth-mark"><i class="fas fa-university"></i></div>
                <div>
                    <div style="font-size:15px; font-weight:600; letter-spacing:.2px;">NIMR</div>
                    <div style="font-size:11px; color:rgba(255,255,255,.45);">Grants Management Unit</div>
                </div>
            </div>

            <h1 style="margin-top:52px; font-size:29px; line-height:1.28; letter-spacing:-.7px; max-width:16ch;">
                One place for every grant NIMR holds.
            </h1>

            <div class="auth-points">
                <div class="auth-point">
                    <i class="fas fa-check"></i>
                    <span>Funding calls, the proposal pipeline, and institutional approval through to the Director General.</span>
                </div>
                <div class="auth-point">
                    <i class="fas fa-check"></i>
                    <span>Award compliance: reporting calendars, ethics renewals, sub-awards and the expenditure ledger.</span>
                </div>
                <div class="auth-point">
                    <i class="fas fa-check"></i>
                    <span>Closeout and lessons learned, so what one grant teaches carries into the next.</span>
                </div>
            </div>
        </div>

        <div style="font-size:11px; color:rgba(255,255,255,.34); line-height:1.7;">
            National Institute for Medical Research<br>
            Established under the NIMR Act [Cap. 59 R.E. 2023]
        </div>
    </aside>

    <main class="auth-form">
        <div class="auth-card">
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
