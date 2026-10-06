<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Forgot Password | ElevateHer360</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        .eh-staff-login-form .eh-auth-input-wrap{position:relative;display:flex;align-items:center}
        .eh-staff-login-form .eh-auth-input-wrap>.fa-envelope{position:absolute;left:14px;top:50%;transform:translateY(-50%);z-index:2;pointer-events:none}
        .eh-staff-login-form .eh-auth-input-wrap input{width:100%;min-width:0;padding-left:42px}
        .eh-staff-login-brand h1{color:#fff!important}
        .eh-staff-login-brand::before,.eh-staff-login-brand::after,.eh-staff-login-decoration{display:none!important;content:none!important}
    </style>
</head>
<body class="eh-staff-login-body">
<main class="eh-staff-login-shell">
    <section class="eh-staff-login-brand" aria-label="WITU staff portal">
        <div class="eh-staff-login-brand-content">
            <div class="eh-staff-login-badge">WITU STAFF PORTAL</div>
            <h1>Reset your staff password.</h1>
            <p>We will email a secure reset link to your authorised staff email address.</p>
        </div>
    </section>

    <section class="eh-staff-login-form-side">
        <div class="eh-staff-login-card">
            <x-back-to-website class="eh-back-home--card" />
            <div class="eh-staff-login-heading">
                <span class="eh-staff-authorised-badge"><i class="fas fa-shield-halved"></i> Authorised staff only</span>
                <h2>Forgot Password</h2>
                <p>Enter your work email to receive a reset link.</p>
            </div>

            @if(session('success'))
                <div class="form-alert form-alert-success" data-auto-dismiss><i class="fas fa-circle-check"></i><span>{{ session('success') }}</span></div>
            @endif
            @if($errors->any())
                <div class="form-alert form-alert-error" data-auto-dismiss><i class="fas fa-circle-exclamation"></i><span>{{ $errors->first() }}</span></div>
            @endif

            <form method="POST" action="{{ route('admin.password.email') }}" class="eh-staff-login-form">
                @csrf
                <div class="eh-auth-field">
                    <label for="staffResetEmail">Work Email *</label>
                    <div class="eh-auth-input-wrap">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <input id="staffResetEmail" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="name@witu.org">
                    </div>
                </div>

                <button class="btn btn-primary eh-staff-login-submit"><i class="fas fa-paper-plane"></i> Send Reset Link</button>
            </form>

            <p class="eh-staff-login-footnote">
                <a href="{{ route('admin.login') }}" class="eh-forgot-link"><i class="fas fa-arrow-left"></i> Back to staff sign in</a>
            </p>
        </div>
    </section>
</main>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('[data-auto-dismiss]').forEach((message)=>{
        setTimeout(()=>{message.classList.add('is-fading');setTimeout(()=>message.remove(),450)},5000);
    });
});
</script>
</body>
</html>
