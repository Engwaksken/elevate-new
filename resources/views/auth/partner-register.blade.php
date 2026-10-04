@extends('layouts.app')

@php
    $isMentor = $type === 'mentor';
    $label = $isMentor ? 'Mentor' : 'Employer';
    $title = $content['title'] ?? ($isMentor ? 'Become a Mentor' : 'Register an Employer');
    $summary = $content['summary'] ?? 'Submit your details for administrator review.';
    $fieldLabels = data_get($content, 'settings.field_labels', []);
@endphp

@section('title', $title.' | ElevateHer360')
@section('meta_description', $summary)

@section('content')

<div class="eh-register-shell">

    {{-- LEFT PANEL --}}
    <section class="eh-register-hero">
        <div class="eh-register-hero-inner">
            <div class="eh-register-gold-line"></div>

            <span class="eh-register-badge">
                <i class="fas {{ $isMentor ? 'fa-user-tie' : 'fa-building' }}"></i>
                {{ strtoupper($label) }} REGISTRATION
            </span>

            <h1>
                {{ $isMentor ? 'Share your experience as a mentor.' : 'Partner with us as an employer.' }}
            </h1>

            <p>
                {{ $isMentor
                    ? 'Guide participants through mentorship sessions, set goals and help them grow into their careers.'
                    : 'Post opportunities, review applicants and connect with skilled participants ready for work.' }}
            </p>

            <div class="eh-register-benefits">
                <div class="eh-register-benefit">
                    <span><i class="fas {{ $isMentor ? 'fa-handshake' : 'fa-briefcase' }}"></i></span>
                    <div>
                        <strong>{{ $isMentor ? 'Mentor participants' : 'Post opportunities' }}</strong>
                        <small>{{ $isMentor ? 'Run sessions and track mentee progress.' : 'Publish jobs and manage applications.' }}</small>
                    </div>
                </div>

                <div class="eh-register-benefit">
                    <span><i class="fas {{ $isMentor ? 'fa-bullseye' : 'fa-users' }}"></i></span>
                    <div>
                        <strong>{{ $isMentor ? 'Set growth goals' : 'Meet talent' }}</strong>
                        <small>{{ $isMentor ? 'Support goals and session reports.' : 'Review applicants and shortlist candidates.' }}</small>
                    </div>
                </div>

                <div class="eh-register-benefit">
                    <span><i class="fas fa-chart-line"></i></span>
                    <div>
                        <strong>Track impact</strong>
                        <small>See engagement and placement outcomes.</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FORM PANEL --}}
    <section class="eh-register-form-side">
        <div class="eh-register-form-inner">
            <div class="eh-register-heading">
                <div>
                    <span class="eh-register-step-count">Create account</span>
                    <h2>{{ $title }}</h2>
                    <p>{{ $summary }}</p>
                </div>
            </div>

            @include('partials.form-feedback')
            @include('partials.cms-intro', ['slug' => $type.'-signup'])

            @if(data_get($content, 'settings.signup_open', true))
                <form method="POST" action="{{ route('public.partners.'.$type.'.store') }}" class="eh-register-form">
                    @csrf

                    @include('partials.partner-fields', ['fieldLabels' => $fieldLabels, 'profile' => $profile, 'type' => $type])

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="partner-password">Password <span aria-hidden="true">*</span></label>
                            <div class="eh-login-input-wrap">
                                <i class="fas fa-lock"></i>
                                <input id="partner-password" type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password">
                                <button type="button" class="eh-login-password-toggle" data-password-toggle="partner-password" aria-label="Show or hide password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="form-hint">Minimum 8 characters with mixed case and a number.</small>
                        </div>

                        <div class="form-group">
                            <label for="partner-password-confirmation">Confirm password <span aria-hidden="true">*</span></label>
                            <input id="partner-password-confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                        </div>
                    </div>

                    <label class="modal-check">
                        <input type="checkbox" name="consent" value="1" required @checked(old('consent'))>
                        <span>I agree to the <a href="{{ route('legal.privacy') }}">Privacy Policy</a> and <a href="{{ route('legal.terms') }}">Terms of Use</a>.</span>
                    </label>

                    <button class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> {{ data_get($content, 'settings.button_label') ?: 'Submit registration' }}
                    </button>
                </form>

                <div class="eh-login-divider"><span>Already registered?</span></div>

                <a href="{{ route('partners.'.$type.'.login') }}" class="eh-login-create">
                    <i class="fas fa-right-to-bracket"></i>
                    Sign in to your {{ strtolower($label) }} account
                </a>
            @else
                <div class="info-box">Registrations are currently closed.</div>
            @endif
        </div>
    </section>
</div>

@endsection
