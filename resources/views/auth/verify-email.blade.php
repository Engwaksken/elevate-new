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
.verify-email-email{font-weight:700;color:#101828;overflow-wrap:anywhere}
.verify-email-steps{text-align:left;margin:18px 0 0;padding:14px 16px 14px 34px;background:#faf7f4;border-radius:12px;color:#475467;font-size:.92rem;line-height:1.6}
.verify-email-waiting{margin-top:18px;display:flex;align-items:center;justify-content:center;gap:8px;font-size:.9rem;color:#667085}
.verify-email-waiting.is-done{color:#067647;font-weight:700}
.verify-email-actions{margin-top:20px;display:grid;gap:10px}
.verify-email-actions .btn{width:100%}
.verify-email-note{margin-top:18px;font-size:.8rem;color:#98a2b3}
</style>

<div class="verify-email-card">
    <div class="verify-email-icon"><i class="fas fa-envelope-circle-check"></i></div>
    <h1>Check your email</h1>
    <p>Your account has been created. To continue, verify your email address.</p>
    <p>We sent a verification link to <span class="verify-email-email">{{ auth()->user()?->email }}</span>.</p>

    @if(session('status'))
        <div class="alert alert-success" style="margin-top:14px">{{ session('status') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success" style="margin-top:14px">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error" style="margin-top:14px">{{ $errors->first() }}</div>
    @endif

    <ol class="verify-email-steps">
        <li>Open the email from ElevateHer360.</li>
        <li>Click <strong>Verify email address</strong>.</li>
        <li>Your dashboard opens automatically. You can click the link on your phone and keep this page open.</li>
    </ol>

    <div class="verify-email-waiting" data-verify-status role="status" aria-live="polite">
        <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
        <span>Waiting for you to verify your email…</span>
    </div>

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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const statusBox = document.querySelector('[data-verify-status]');
    const statusUrl = @json(route('verification.status'));
    let timer = null;

    async function check() {
        try {
            const response = await fetch(statusUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (!response.ok) return;

            const data = await response.json();
            if (data.verified && data.redirect) {
                clearInterval(timer);
                if (statusBox) {
                    statusBox.classList.add('is-done');
                    statusBox.innerHTML = '<i class="fas fa-circle-check" aria-hidden="true"></i><span>Email verified. Opening your dashboard…</span>';
                }
                window.location.assign(data.redirect);
            }
        } catch (e) {
            // Network blip: try again on the next tick.
        }
    }

    timer = setInterval(function () {
        if (!document.hidden) check();
    }, 5000);

    // Check straight away when they come back from their email tab.
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) check();
    });
});
</script>
@endpush
