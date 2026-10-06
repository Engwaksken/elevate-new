@extends('layouts.app')
@section('auth_back_inline', '1')

@section('title', 'Reset Password | ElevateHer360')

@section('content')
<div class="eh-login-page">
    <div class="eh-login-shell">
        <section class="eh-login-side">
            <div class="eh-login-side__inner">
                <x-back-to-website tone="dark" class="eh-back-home--panel" />
                <span class="eh-login-side__eyebrow">ElevateHer360</span>

                <h1>Create a new password.</h1>

                <p class="eh-login-side__intro">
                    Choose a strong password to secure your account. You will use it the
                    next time you sign in.
                </p>

                <div class="eh-login-benefits">
                    <div><i class="fas fa-lock"></i><span>At least 8 characters</span></div>
                    <div><i class="fas fa-font"></i><span>Mix upper and lower case letters</span></div>
                    <div><i class="fas fa-hashtag"></i><span>Include at least one number</span></div>
                </div>
            </div>
        </section>

        <section class="eh-login-panel">
            <div class="eh-login-card">
                <div class="eh-login-card__header">
                    <span class="eh-login-card__badge">
                        <i class="fas fa-shield-halved"></i>
                    </span>

                    <div>
                        <span class="eh-login-card__eyebrow">Secure reset</span>
                        <h2>Reset Password</h2>
                        <p>Your new password must contain at least 8 characters, mixed case and a number.</p>
                    </div>
                </div>

                @if($errors->any())
                    <div class="alert alert-error">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="eh-login-form">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="eh-login-field">
                        <label for="reset_email">
                            Email address <span aria-hidden="true">*</span>
                        </label>
                        <div class="eh-login-input-wrap">
                            <i class="fas fa-envelope"></i>
                            <input id="reset_email"
                                   type="email"
                                   name="email"
                                   value="{{ old('email', $email) }}"
                                   autocomplete="email"
                                   required>
                        </div>
                    </div>

                    <div class="eh-login-field">
                        <label for="reset_password">
                            New password <span aria-hidden="true">*</span>
                        </label>
                        <div class="eh-login-input-wrap">
                            <i class="fas fa-lock"></i>
                            <input id="reset_password"
                                   type="password"
                                   name="password"
                                   placeholder="Enter new password"
                                   autocomplete="new-password"
                                   required>
                            <button type="button"
                                    class="eh-login-password-toggle"
                                    data-password-toggle="reset_password"
                                    aria-label="Show or hide password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="eh-login-field">
                        <label for="reset_password_confirmation">
                            Confirm new password <span aria-hidden="true">*</span>
                        </label>
                        <div class="eh-login-input-wrap">
                            <i class="fas fa-lock"></i>
                            <input id="reset_password_confirmation"
                                   type="password"
                                   name="password_confirmation"
                                   placeholder="Repeat new password"
                                   autocomplete="new-password"
                                   required>
                            <button type="button"
                                    class="eh-login-password-toggle"
                                    data-password-toggle="reset_password_confirmation"
                                    aria-label="Show or hide password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="eh-login-submit">
                        <i class="fas fa-key"></i>
                        <span>Reset Password</span>
                    </button>
                </form>

                <div class="eh-login-divider">
                    <span>Sign in instead</span>
                </div>

                <div style="display:grid;gap:10px">
                    <a href="{{ route('login') }}" class="eh-login-create">
                        <i class="fas fa-user-graduate"></i>
                        Participant sign in
                    </a>
                    @if(Route::has('admin.login'))
                        <a href="{{ route('admin.login') }}" class="eh-login-create">
                            <i class="fas fa-shield-halved"></i>
                            Staff sign in
                        </a>
                    @endif
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
