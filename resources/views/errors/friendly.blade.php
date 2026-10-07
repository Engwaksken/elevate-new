<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Something went wrong' }} | ElevateHer360</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        body{margin:0;background:#f7f7f7;color:#1f2937;font-family:Arial,Helvetica,sans-serif}
        .eh-friendly-error{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:28px}
        .eh-friendly-error__card{width:min(100%,620px);background:#fff;border:1px solid #e5e7eb;border-top:4px solid #800000;border-radius:16px;padding:34px;box-shadow:0 18px 50px rgba(0,0,0,.08);text-align:center}
        .eh-friendly-error__logo{display:block;width:auto;height:auto;max-width:min(220px,70%);max-height:96px;object-fit:contain;margin:0 auto 22px}
        .eh-friendly-error__icon{width:64px;height:64px;margin:0 auto 18px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#fff7da;color:#800000;font-size:25px}
        .eh-friendly-error h1{margin:0 0 10px;color:#800000;font-size:1.7rem}
        .eh-friendly-error p{margin:0 auto 24px;max-width:500px;color:#667085;line-height:1.65}
        .eh-friendly-error__actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
        .eh-friendly-error__actions a,.eh-friendly-error__actions button{border-radius:9px;padding:10px 16px;font-weight:700;cursor:pointer;text-decoration:none}
        .eh-friendly-error__primary{background:#800000;color:#fff!important;border:1px solid #800000}
        .eh-friendly-error__secondary{background:#fff;border:1px solid #d0d5dd;color:#344054}
    </style>
</head>
<body>
@php
    $errorUser = auth()->user();
    $dashboardRoute = null;

    if ($errorUser) {
        if (method_exists($errorUser, 'isStaff') && $errorUser->isStaff() && \Illuminate\Support\Facades\Route::has('admin.dashboard')) {
            $dashboardRoute = route('admin.dashboard');
        } elseif (\Illuminate\Support\Facades\Route::has('dashboard')) {
            $dashboardRoute = route('dashboard');
        }
    }

    $homeRoute = \Illuminate\Support\Facades\Route::has('home') ? route('home') : url('/');

    $errorLogoUrl = null;
    try {
        $logoPath = app(\App\Services\SettingsService::class)->get('branding.logo_path');
        if ($logoPath && \Illuminate\Support\Facades\Route::has('branding.asset')) {
            $errorLogoUrl = route('branding.asset', ['type' => 'logo', 'v' => md5((string) $logoPath)]);
        }
    } catch (\Throwable) {
        // Keep the error page usable even if the settings store is unavailable.
    }
@endphp
<main class="eh-friendly-error">
    <section class="eh-friendly-error__card">
        @if($errorLogoUrl)
            <img class="eh-friendly-error__logo" src="{{ $errorLogoUrl }}" alt="ElevateHer360 logo">
        @else
            <div class="eh-friendly-error__icon"><i class="fas {{ $icon ?? 'fa-circle-info' }}"></i></div>
        @endif
        <h1>{{ $title ?? 'We could not complete that request' }}</h1>
        <p>{{ $message ?? 'Please return to a page you can access and try again.' }}</p>
        <div class="eh-friendly-error__actions">
            <button type="button" class="eh-friendly-error__secondary" onclick="history.back()">Go Back</button>
            <a class="eh-friendly-error__primary" href="{{ $dashboardRoute ?: $homeRoute }}">
                {{ $dashboardRoute ? 'Go to Dashboard' : 'Go to Home' }}
            </a>
        </div>
    </section>
</main>
</body>
</html>
