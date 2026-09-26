<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Sign In | ElevateHer360</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        .eh-staff-login-form .eh-auth-input-wrap{position:relative;display:flex;align-items:center}
        .eh-staff-login-form .eh-auth-input-wrap>.fa-lock,
        .eh-staff-login-form .eh-auth-input-wrap>.fa-envelope{position:absolute;left:14px;top:50%;transform:translateY(-50%);z-index:2;pointer-events:none}
        .eh-staff-login-form .eh-auth-input-wrap input{width:100%;min-width:0;padding-left:42px}
        .eh-staff-login-form .eh-auth-input-wrap input[type="password"],
        .eh-staff-login-form .eh-auth-input-wrap input[type="text"]{padding-right:48px}
        .eh-staff-login-form .eh-password-toggle{position:absolute;right:8px;top:50%;transform:translateY(-50%);width:36px;height:36px;display:inline-flex!important;align-items:center;justify-content:center;margin:0;padding:0;border:0;border-radius:8px;background:transparent;color:#5f6670;cursor:pointer;z-index:4;line-height:1}
        .eh-staff-login-form .eh-password-toggle:hover,
        .eh-staff-login-form .eh-password-toggle:focus-visible{color:#800000;background:rgba(128,0,0,.07);outline:none}
        .eh-staff-login-form .eh-password-toggle i{position:static!important;transform:none!important;font-size:16px;pointer-events:none}
    </style>
</head>
<body class="eh-staff-login-body">
<main class="eh-staff-login-shell">
    <section class="eh-staff-login-brand" aria-label="WITU staff portal">
        <div class="eh-staff-login-brand-content">
            <div class="eh-staff-login-badge">WITU STAFF PORTAL</div>
            <h1>Manage programmes and participant impact.</h1>
            <p>Secure access for authorised WITU staff, programme teams and administrators.</p>
        </div>
        <div class="eh-staff-login-decoration" aria-hidden="true"></div>
    </section>

    <section class="eh-staff-login-form-side">
        <div class="eh-staff-login-card">
            <div class="eh-staff-login-heading">
                <span class="eh-staff-authorised-badge"><i class="fas fa-shield-halved"></i> Authorised staff only</span>
                <h2>Staff Sign In</h2>
                <p>Enter your authorised WITU staff credentials.</p>
            </div>

            @if(session('success'))
                <div class="form-alert form-alert-success" data-auto-dismiss><i class="fas fa-circle-check"></i><span>{{ session('success') }}</span></div>
            @endif
            @if(session('error'))
                <div class="form-alert form-alert-error" data-auto-dismiss><i class="fas fa-circle-exclamation"></i><span>{{ session('error') }}</span></div>
            @endif
            @if($errors->any())
                <div class="form-alert form-alert-error" data-auto-dismiss><i class="fas fa-circle-exclamation"></i><span>{{ $errors->first() }}</span></div>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}" class="eh-staff-login-form">
                @csrf
                <div class="eh-auth-field">
                    <label for="staffEmail">Work Email *</label>
                    <div class="eh-auth-input-wrap">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <input id="staffEmail" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="name@witu.org">
                    </div>
                </div>

                <div class="eh-auth-field">
                    <label for="staffPassword">Password *</label>
                    <div class="eh-auth-input-wrap">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <input id="staffPassword" type="password" name="password" autocomplete="current-password" required placeholder="Enter password">
                        <button type="button" class="eh-password-toggle" data-password-toggle="staffPassword" aria-label="Show password" aria-controls="staffPassword" aria-pressed="false" title="Show password">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="eh-auth-row">
                    <label class="eh-remember">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        <span>Remember me</span>
                    </label>
                    @if(Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="eh-forgot-link">Forgot password?</a>
                    @endif
                </div>

                <button class="btn btn-primary eh-staff-login-submit"><i class="fas fa-shield-halved"></i> Sign In</button>
            </form>

            <p class="eh-staff-login-footnote">This portal is restricted to authorised WITU staff accounts.</p>
        </div>
    </section>
</main>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('[data-password-toggle]').forEach((button)=>{
        button.addEventListener('click',()=>{
            const input=document.getElementById(button.dataset.passwordToggle);
            if(!input)return;
            const show=input.type==='password';
            input.type=show?'text':'password';
            const icon=button.querySelector('i');
            icon?.classList.toggle('fa-eye',!show);
            icon?.classList.toggle('fa-eye-slash',show);
            const label=show?'Hide password':'Show password';
            button.setAttribute('aria-label',label);
            button.setAttribute('title',label);
            button.setAttribute('aria-pressed',show?'true':'false');
        });
    });
    document.querySelectorAll('[data-auto-dismiss]').forEach((message)=>{
        setTimeout(()=>{message.classList.add('is-fading');setTimeout(()=>message.remove(),450)},5000);
    });
});
</script>
</body>
</html>
