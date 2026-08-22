<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In – NIMR Grants Management Unit</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <style>
        body { background: #003366; }
        .login-box { margin-top: 8vh; }
        .login-logo a {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .login-logo small {
            display: block;
            font-size: 0.8rem;
            font-weight: 300;
            color: rgba(255,255,255,0.75);
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .login-card-body {
            border-top: 4px solid #003366;
        }
        .btn-primary {
            background-color: #003366;
            border-color: #003366;
        }
        .btn-primary:hover {
            background-color: #00254d;
            border-color: #00254d;
        }
        .nimr-badge {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            color: #fff;
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 12px;
            letter-spacing: 0.5px;
            margin-top: 6px;
        }
    </style>
</head>
<body class="hold-transition login-page">

<div class="login-box">
    <div class="login-logo text-center mb-3">
        <a href="#">
            <i class="fas fa-microscope mr-2"></i>NIMR
            <small>Grants Management Unit</small>
        </a>
        <div class="nimr-badge">National Institute for Medical Research · Tanzania</div>
    </div>

    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg text-muted">Sign in to your account</p>

            @if (session('status'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="input-group mb-3">
                    <input id="email" type="email" name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}"
                           placeholder="Email address"
                           required autofocus autocomplete="username">
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-envelope"></span></div>
                    </div>
                </div>

                <div class="input-group mb-3">
                    <input id="password" type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Password"
                           required autocomplete="current-password">
                    <div class="input-group-append">
                        <div class="input-group-text"><span class="fas fa-lock"></span></div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-7">
                        <div class="icheck-primary">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt mr-1"></i> Sign In
                </button>
            </form>

            @if (Route::has('password.request'))
            <p class="mt-3 mb-1 text-center">
                <a href="{{ route('password.request') }}" class="text-muted small">
                    <i class="fas fa-key mr-1"></i>Forgot your password?
                </a>
            </p>
            @endif
        </div>
    </div>

    <p class="text-center" style="color:rgba(255,255,255,0.4);font-size:11px;margin-top:10px">
        &copy; {{ date('Y') }} NIMR GMU &middot; Authorised personnel only
    </p>
</div>

<script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>
</body>
</html>
