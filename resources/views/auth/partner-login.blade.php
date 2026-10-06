@extends('layouts.app')
@section('auth_back_inline', '1')

@php
    $isMentor = $type === 'mentor';
    $label = $isMentor ? 'Mentor' : 'Employer';
@endphp

@section('title', ucfirst($type).' Login | ElevateHer360')

@section('content')
<div class="eh-login-page">
    <div class="eh-login-shell">
        <section class="eh-login-side">
            <div class="eh-login-side__inner">
                <x-back-to-website tone="dark" class="eh-back-home--panel" />
                <span class="eh-login-side__eyebrow">ElevateHer360</span>

                <h1>{{ $isMentor ? 'Welcome back, mentor.' : 'Welcome back, employer.' }}</h1>

                <p class="eh-login-side__intro">
                    {{ $isMentor
                        ? 'Sign in to manage your mentorship sessions, goals and mentees.'
                        : 'Sign in to post opportunities, review applicants and track placements.' }}
                </p>

                <div class="eh-login-benefits">
                    @if($isMentor)
                        <div><i class="fas fa-handshake"></i><span>Manage mentorship sessions</span></div>
                        <div><i class="fas fa-bullseye"></i><span>Review mentee goals</span></div>
                        <div><i class="fas fa-file-lines"></i><span>Submit session reports</span></div>
                        <div><i class="fas fa-chart-line"></i><span>Track engagement</span></div>
                    @else
                        <div><i class="fas fa-briefcase"></i><span>Post and manage opportunities</span></div>
                        <div><i class="fas fa-users"></i><span>Review applicants</span></div>
                        <div><i class="fas fa-comments"></i><span>Schedule interviews</span></div>
                        <div><i class="fas fa-handshake"></i><span>Track placements</span></div>
                    @endif
                </div>
            </div>
        </section>

        <section class="eh-login-panel">
            <div class="eh-login-card">
                <div class="eh-login-card__header">
                    <span class="eh-login-card__badge">
                        <i class="fas {{ $isMentor ? 'fa-user-tie' : 'fa-building' }}"></i>
                    </span>

                    <div>
                        <span class="eh-login-card__eyebrow">{{ $label }} Portal</span>
                        <h2>{{ $label }} Sign In</h2>
                        <p>Use the email address linked to your {{ strtolower($label) }} account.</p>
                    </div>
                </div>

                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-error">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('partners.'.$type.'.login.attempt') }}" class="eh-login-form">
                    @csrf

                    <div class="eh-login-field">
                        <label for="email">Email address <span aria-hidden="true">*</span></label>
                        <div class="eh-login-input-wrap">
                            <i class="fas fa-envelope"></i>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="e.g. name@example.com" autocomplete="email" required autofocus>
                        </div>
                    </div>

                    <div class="eh-login-field">
                        <div class="eh-login-label-row">
                            <label for="partner_login_password">Password <span aria-hidden="true">*</span></label>
                            @if(Route::has('password.request'))
                                <a href="{{ route('password.request') }}">Forgot password?</a>
                            @endif
                        </div>

                        <div class="eh-login-input-wrap">
                            <i class="fas fa-lock"></i>
                            <input id="partner_login_password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                            <button type="button" class="eh-login-password-toggle" data-password-toggle="partner_login_password" aria-label="Show or hide password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <label class="eh-login-remember" for="remember">
                        <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        <span>Remember me</span>
                    </label>

                    <button type="submit" class="eh-login-submit">
                        <i class="fas fa-right-to-bracket"></i>
                        <span>Sign In</span>
                    </button>
                </form>

                <div class="eh-login-divider"><span>New to ElevateHer360?</span></div>

                <a href="{{ route('public.partners.'.$type) }}" class="eh-login-create">
                    <i class="fas fa-user-plus"></i>
                    Create {{ strtolower($label) }} account
                </a>
            </div>
        </section>
    </div>
</div>
@endsection
