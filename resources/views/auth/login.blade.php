@extends('layouts.app')

@section('title', 'Participant Login | ElevateHer360')

@section('content')
<div class="eh-login-page">
    <div class="eh-login-shell">
        <section class="eh-login-side">
            <div class="eh-login-side__inner">
                <span class="eh-login-side__eyebrow">ElevateHer360</span>

                <h1>Welcome back to ElevateHer360.</h1>

                <p class="eh-login-side__intro">
                    Sign in with your participant account to continue your learning,
                    mentorship, career and employment journey.
                </p>

                <div class="eh-login-benefits">
                    <div><i class="fas fa-graduation-cap"></i><span>Continue courses and assessments</span></div>
                    <div><i class="fas fa-user-group"></i><span>Connect with your mentor</span></div>
                    <div><i class="fas fa-file-lines"></i><span>Build and manage your resume</span></div>
                    <div><i class="fas fa-briefcase"></i><span>Apply for opportunities</span></div>
                    <div><i class="fas fa-chart-line"></i><span>Track your progress and outcomes</span></div>
                </div>
            </div>
        </section>

        <section class="eh-login-panel">
            <div class="eh-login-card">
                <div class="eh-login-card__header">
                    <span class="eh-login-card__badge">
                        <i class="fas fa-user-graduate"></i>
                    </span>

                    <div>
                        <span class="eh-login-card__eyebrow">Participant Portal</span>
                        <h2>Sign In</h2>
                        <p>Use the email address linked to your ElevateHer360 account.</p>
                    </div>
                </div>

                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if(session('error'))
                    <div class="alert alert-error">{{ session('error') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-error">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST"
                      action="{{ route('login.attempt') }}"
                      class="eh-login-form">
                    @csrf

                    <div class="eh-login-field">
                        <label for="email">
                            Email address <span aria-hidden="true">*</span>
                        </label>

                        <div class="eh-login-input-wrap">
                            <i class="fas fa-envelope"></i>

                            <input id="email"
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

                    <div class="eh-login-field">
                        <div class="eh-login-label-row">
                            <label for="participant_password">
                                Password <span aria-hidden="true">*</span>
                            </label>

                            @if(Route::has('password.request'))
                                <a href="{{ route('password.request') }}">Forgot password?</a>
                            @endif
                        </div>

                        <div class="eh-login-input-wrap">
                            <i class="fas fa-lock"></i>

                            <input id="participant_password"
                                   type="password"
                                   name="password"
                                   placeholder="Enter your password"
                                   autocomplete="current-password"
                                   required>

                            <button type="button"
                                    class="eh-login-password-toggle"
                                    data-password-toggle="participant_password"
                                    aria-label="Show or hide password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>

                        <small>Required. Enter the password for your ElevateHer360 account.</small>
                    </div>

                    <label class="eh-login-remember" for="remember">
                        <input id="remember"
                               type="checkbox"
                               name="remember"
                               value="1"
                               @checked(old('remember'))>
                        <span>Remember me</span>
                    </label>

                    <button type="submit" class="eh-login-submit">
                        <i class="fas fa-right-to-bracket"></i>
                        <span>Sign In</span>
                    </button>
                </form>

                <div class="eh-login-divider">
                    <span>New to ElevateHer360?</span>
                </div>

                <a href="{{ route('register') }}" class="eh-login-create">
                    <i class="fas fa-user-plus"></i>
                    Create participant account
                </a>
            </div>
        </section>
    </div>
</div>
@endsection
