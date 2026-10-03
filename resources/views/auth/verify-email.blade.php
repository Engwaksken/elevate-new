@extends('layouts.app')
@section('title','Verify your email | ElevateHer360')
@section('content')
<div class="verify-email-page">
<style>
.verify-email-page{max-width:560px;margin:32px auto}
.verify-email-card{background:#fff;border:1px solid #eadede;border-radius:16px;padding:30px;text-align:center;box-shadow:0 1px 2px rgba(16,24,40,.04)}
.verify-email-icon{width:64px;height:64px;border-radius:18px;display:grid;place-items:center;background:#fff7da;color:#800000;font-size:26px;margin:0 auto 16px}
.verify-email-card h1{margin:0 0 8px;color:#101828}
.verify-email-card p{color:#667085;margin:0 0 8px}
.verify-email-email{font-weight:700;color:#101828}
.verify-email-actions{margin-top:20px;display:grid;gap:10px}
.verify-email-actions .btn{width:100%}
.verify-email-note{margin-top:18px;font-size:.8rem;color:#98a2b3}
</style>

<div class="verify-email-card">
    <div class="verify-email-icon"><i class="fas fa-envelope-circle-check"></i></div>
    <h1>Verify your email</h1>
    <p>Please check your inbox and click the verification link.</p>
    <p>We sent the link to <span class="verify-email-email">{{ auth()->user()?->email }}</span>.</p>

    @if(session('status'))
        <div class="alert alert-success" style="margin-top:14px">{{ session('status') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success" style="margin-top:14px">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error" style="margin-top:14px">{{ $errors->first() }}</div>
    @endif

    <div class="verify-email-actions">
        <form method="POST" action="{{ route('verification.send') }}">@csrf
            <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i> Resend verification email</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button class="btn btn-outline" type="submit"><i class="fas fa-right-from-bracket"></i> Sign out</button>
        </form>
    </div>

    <p class="verify-email-note">You must verify your email before you can open your dashboard. If the email has not arrived, check your spam folder or resend it above.</p>
</div>
</div>
@endsection
