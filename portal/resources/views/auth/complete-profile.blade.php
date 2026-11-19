<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Confirm your details · NIMR Grants</title>
    <link rel="icon" type="image/png" href="{{ asset('images/nimr-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/gmu.css') }}?v={{ filemtime(public_path('css/gmu.css')) }}">
    <style>
        .ob-head { background:#fff; border-bottom:1px solid var(--line); }
        .ob-head-in { max-width:1320px; margin:0 auto; padding:12px 32px; display:flex; flex-wrap:wrap; align-items:center;
            justify-content:space-between; gap:12px; }
        .ob-main { max-width:860px; margin:0 auto; padding:56px 24px 80px; }
        .ro { min-height:44px; padding:10px 12px; border-radius:var(--radius-sm); background:var(--canvas);
            border:1px dashed var(--line-2); display:flex; align-items:center; justify-content:space-between; gap:8px; }
        .choices { display:flex; flex-wrap:wrap; gap:12px; }
        .choice { flex:1 1 220px; display:flex; gap:12px; align-items:flex-start; padding:14px 16px; border:1px solid var(--line-2);
            border-radius:12px; cursor:pointer; }
        .choice:has(input:checked) { border-color:var(--brand); background:var(--blue-50); box-shadow:inset 0 0 0 1px var(--brand); }
        .choice input { margin-top:3px; width:18px; height:18px; accent-color:var(--brand); }
        .elig { display:flex; gap:14px; align-items:flex-start; padding:18px 20px; border-radius:16px; margin-top:20px; }
        .elig.ok { background:var(--ok-bg); color:var(--blue-900); }
        .elig.no { background:var(--warn-bg); color:var(--warn-ink); }
        .elig .ico { flex:0 0 32px; width:32px; height:32px; border-radius:50%; display:grid; place-items:center; color:#fff; }
        .elig.ok .ico { background:var(--brand); }
        .elig.no .ico { background:var(--warn); }
        @media (max-width:900px) { .ob-head-in { padding:12px 16px; } .ob-main { padding:32px 16px 64px; } }
    </style>
</head>
<body>
<header class="ob-head">
    <div class="ob-head-in">
        <span class="row" style="gap:12px;">
            <img src="{{ asset('images/nimr-logo.png') }}" alt="" style="width:40px; height:40px; object-fit:contain;">
            <strong style="font-size:15px;">NIMR Grants</strong>
        </span>
        <span class="row small muted" style="gap:6px 14px;">
            <span>Signed in through NREIMS as <strong style="color:var(--ink);">{{ $user->email }}</strong></span>
            <form method="POST" action="{{ route('logout') }}" class="inline-form">
                @csrf
                <button type="submit" style="border:0; background:none; padding:0; min-height:44px; color:var(--blue-700); font-weight:600; cursor:pointer;">Sign out</button>
            </form>
        </span>
    </div>
</header>

<main class="ob-main">
    <p class="eyebrow" style="color:var(--blue-700);">Welcome to NIMR Grants · one-time setup</p>
    <h1 style="margin-top:10px; font-size:clamp(28px,3vw,36px); letter-spacing:-0.025em;">Confirm your details, {{ \Illuminate\Support\Str::of($user->name)->before(' ') }}.</h1>
    <p style="margin-top:12px; font-size:16px; color:var(--ink-2); max-width:640px;">
        Your name and email came across from NREIMS. Tell us about your post so we can check eligibility for the seed grant.
        You only do this once.
    </p>

    @if($errors->any())
        <div class="alert alert-error" role="alert" style="margin-top:24px;">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <div>
                <strong>Please check {{ $errors->count() === 1 ? 'one field' : $errors->count() . ' fields' }}</strong>
                <ul style="margin:6px 0 0; padding-left:18px;">@foreach($errors->all() as $m)<li>{{ $m }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    <section class="card" style="margin-top:32px;">
        <div class="card-body">
            <div class="row-between">
                <h2 class="card-title">From your NREIMS account</h2>
                <span class="tiny muted">Change these in NREIMS</span>
            </div>
            <div class="form-grid" style="margin-top:16px;">
                <div class="field"><span class="label">Full name</span><div class="ro"><span>{{ $user->name }}</span><i class="fas fa-lock muted" aria-label="Locked"></i></div></div>
                <div class="field"><span class="label">Email</span><div class="ro"><span>{{ $user->email }}</span><i class="fas fa-lock muted" aria-label="Locked"></i></div></div>
            </div>
        </div>
    </section>

    <form method="POST" action="{{ route('profile.complete.store') }}">
        @csrf
        <section class="card" style="margin-top:20px;">
            <div class="card-body">
                <h2 class="card-title">Your post at NIMR</h2>
                <div class="form-grid" style="margin-top:18px;">
                    <div class="field">
                        <label class="label" for="designation">Designation</label>
                        <select id="designation" name="designation" class="select @error('designation') is-invalid @enderror" required>
                            <option value="">Choose…</option>
                            @foreach($designations as $d)
                                <option value="{{ $d }}" @selected(old('designation', $user->designation) === $d)>{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label class="label" for="research_center">Centre</label>
                        <select id="research_center" name="research_center" class="select @error('research_center') is-invalid @enderror" required>
                            <option value="">Choose…</option>
                            @foreach($centres as $c)
                                <option value="{{ $c }}" @selected(old('research_center', $user->research_center) === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label class="label" for="department">Section</label>
                        <input id="department" name="department" class="input @error('department') is-invalid @enderror"
                               value="{{ old('department', $user->department) }}" required maxlength="255">
                    </div>
                    <div class="field">
                        <label class="label" for="phone">Mobile phone</label>
                        <input id="phone" name="phone" class="input mono @error('phone') is-invalid @enderror" inputmode="tel"
                               value="{{ old('phone', $user->phone) }}" placeholder="+255 712 345 678" required>
                        <span class="help">For SMS alerts about deadlines and reviewer requests.</span>
                    </div>

                    <fieldset class="field wide" style="border:0; padding:0; margin:0;">
                        <legend class="label" style="margin-bottom:6px;">Employment type</legend>
                        <div class="choices">
                            @foreach(\App\Models\User::EMPLOYMENT_TYPES as $value => $label)
                                <label class="choice">
                                    <input type="radio" name="employment_type" value="{{ $value }}" required
                                           @checked(old('employment_type', $user->employment_type) === $value)>
                                    <span><strong style="display:block;">{{ $label }}</strong>
                                        <span class="small muted">{{ $value === 'permanent' ? 'On NIMR’s permanent establishment' : 'Fixed-term NIMR contract' }}</span></span>
                                </label>
                            @endforeach
                        </div>
                        <span class="help">Both are eligible to lead an application.</span>
                    </fieldset>

                    <div class="field">
                        <label class="label" for="first_appointment_date">Date of first appointment at NIMR</label>
                        <input id="first_appointment_date" type="date" name="first_appointment_date"
                               class="input @error('first_appointment_date') is-invalid @enderror"
                               value="{{ old('first_appointment_date', $user->first_appointment_date?->toDateString()) }}"
                               max="{{ now()->toDateString() }}" required data-service-date>
                    </div>
                </div>
            </div>
        </section>

        <div class="elig ok" data-elig="ok" hidden>
            <span class="ico"><i class="fas fa-check" aria-hidden="true"></i></span>
            <div>
                <strong style="font-size:15px;">You can lead a seed grant application</strong>
                <p style="margin-top:4px;">You have served at NIMR for <span class="mono" data-service-text></span>. Lead applicants must have served for under {{ \App\Models\User::MAX_SERVICE_YEARS_FOR_SEED_GRANT }} years.</p>
            </div>
        </div>
        <div class="elig no" data-elig="no" hidden>
            <span class="ico"><i class="fas fa-info" aria-hidden="true"></i></span>
            <div>
                <strong style="font-size:15px;">You can still review and use the portal</strong>
                <p style="margin-top:4px;">You have served at NIMR for <span class="mono" data-service-text></span>. Leading a seed grant application needs under {{ \App\Models\User::MAX_SERVICE_YEARS_FOR_SEED_GRANT }} years’ service.</p>
            </div>
        </div>

        <div class="row-between" style="margin-top:28px;">
            <span class="small muted">Reviewers and approvers fill this in too; it does not make you an applicant.</span>
            <button type="submit" class="btn btn-primary">Save and continue <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
        </div>
    </form>
</main>

<script>
    // Show, as the date is entered, whether the service rule for lead applicants is met.
    (function () {
        var input = document.querySelector('[data-service-date]');
        var ok = document.querySelector('[data-elig="ok"]'), no = document.querySelector('[data-elig="no"]');
        var limit = {{ \App\Models\User::MAX_SERVICE_YEARS_FOR_SEED_GRANT }};
        function update() {
            ok.hidden = no.hidden = true;
            if (!input.value) return;
            var start = new Date(input.value + 'T00:00:00'), now = new Date();
            if (isNaN(start) || start > now) return;
            var months = (now.getFullYear() - start.getFullYear()) * 12 + (now.getMonth() - start.getMonth());
            if (now.getDate() < start.getDate()) months--;
            var text = Math.floor(months / 12) + ' years ' + (months % 12) + ' months';
            var box = months < limit * 12 ? ok : no;
            box.querySelector('[data-service-text]').textContent = text;
            box.hidden = false;
        }
        input.addEventListener('input', update);
        update();
    })();
</script>
</body>
</html>
