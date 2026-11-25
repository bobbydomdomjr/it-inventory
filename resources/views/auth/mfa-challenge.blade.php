<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>Verify sign-in · {{ config('app.name') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">@if(file_exists(public_path('build/manifest.json')))@vite(['resources/css/app.css','resources/js/app.js'])@else<link href="{{ asset('css/app.css') }}" rel="stylesheet">@endif
</head>
<body>
<div class="login-page">
 <section class="login-aside"><div class="login-brand"><div class="login-brand-mark">IT</div><div><strong>IT Inventory</strong><span>Management Portal</span></div></div><div class="login-message"><div class="eyebrow">MULTI-FACTOR AUTHENTICATION</div><h1>Verify it’s you.</h1><p>Enter the six-digit code from your authenticator app to finish signing in.</p><div class="login-feature"><i class="bi bi-shield-lock"></i><span>Time-based one-time password (TOTP)</span></div></div><div class="login-foot">Internal system · Authorized users only</div></section>
 <main class="login-main"><div class="login-card"><div class="eyebrow mb-2">SECOND STEP</div><h2>Authentication code</h2><p>Codes refresh approximately every 30 seconds.</p>
 @if($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif
 <form method="POST" action="{{ route('mfa.challenge.verify') }}">@csrf<div class="mb-3"><label class="form-label" for="code">Six-digit code</label><input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control text-center" style="letter-spacing:.35em;font-size:1.4rem;font-variant-numeric:tabular-nums" autocomplete="one-time-code" required autofocus></div><button class="btn btn-primary login-submit" type="submit">Verify and continue <i class="bi bi-arrow-right ms-2"></i></button></form>
 <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<button type="submit" class="btn btn-link w-100 text-secondary text-decoration-none">Sign out</button></form>
 <div class="login-help"><i class="bi bi-info-circle me-1"></i>Do not share authentication codes with anyone.</div>
 </div></main>
</div>
</body></html>
