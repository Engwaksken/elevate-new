@extends('layouts.app')
@section('auth_back_inline', '1')

@section('title', 'Forgot Password | ElevateHer360')

@section('content')
<div class="eh-login-page">
    <div class="eh-login-shell">
        <section class="eh-login-side">
            <div class="eh-login-side__inner">
                <x-back-to-website tone="dark" class="eh-back-home--panel" />
                <span class="eh-login-side__eyebrow">ElevateHer360</span>

                <h1>Reset your password.</h1>

                <p class="eh-login-side__intro">
                    Enter the email address linked to your participant account and we will
                    send you a secure password reset link.
                </p>

                <div class="eh-login-benefits">
                    <div><i class="fas fa-shield-halved"></i><span>Secure, single-use reset link</span></div>
                    <div><i class="fas fa-envelope"></i><span>Sent to your registered email</span></div>
                    <div><i class="fas fa-clock"></i><span>Link expires in 60 minutes</span></div>
                </div>
            </div>
        </section>

        <section class="eh-login-panel">
            <div class="eh-login-card">
                <div class="eh-login-card__header">
                    <span class="eh-login-card__badge">
                        <i class="fas fa-key"></i>
                    </span>

                    <div>
                        <span class="eh-login-card__eyebrow">Participant Portal</span>
                        <h2>Forgot Password</h2>
                        <p>Use the email address registered on your ElevateHer360 account.</p>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-error">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="eh-login-form">
                    @csrf

                    <div class="eh-login-field">
                        <label for="reset_email">
                            Email address <span aria-hidden="true">*</span>
                        </label>

                        <div class="eh-login-input-wrap">
                            <i class="fas fa-envelope"></i>
                            <input id="reset_email"
                                   type="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   placeholder="e.g. name@example.com"
                                   autocomplete="email"
                                   required
                                   autofocus>
                        </div>

                        <small>Required. Use the email address linked to your participant account.</small>
                    </div>

                    <button type="submit" class="eh-login-submit">
                        <i class="fas fa-paper-plane"></i>
                        <span>Send Reset Link</span>
                    </button>
                </form>

                <div class="eh-login-divider">
                    <span>Remembered your password?</span>
                </div>

                <a href="{{ route('login') }}" class="eh-login-create">
                    <i class="fas fa-right-to-bracket"></i>
                    Back to participant sign in
                </a>
            </div>
        </section>
    </div>
</div>
@endsection
