@extends('layouts.app')

@section('title','Participant Login | ElevateHer360')

@section('content')

<div class="eh-auth-shell eh-auth-participant">

    <section class="eh-auth-hero">

        <div class="eh-auth-hero-inner">

            <div class="eh-auth-gold-line"></div>

            <span class="eh-auth-badge">
                <i class="fas fa-user-graduate"></i>
                PARTICIPANT PORTAL
            </span>

            <h1>
                Welcome back to ElevateHer360.
            </h1>

            <p>
                Continue your learning, mentorship,
                career development and employment journey.
            </p>

            <div class="eh-auth-benefits">

                <div class="eh-auth-benefit">

                    <span>
                        <i class="fas fa-graduation-cap"></i>
                    </span>

                    <div>
                        <strong>
                            Continue learning
                        </strong>

                        <small>
                            Access courses, assessments
                            and certificates.
                        </small>
                    </div>

                </div>

                <div class="eh-auth-benefit">

                    <span>
                        <i class="fas fa-user-group"></i>
                    </span>

                    <div>
                        <strong>
                            Mentorship
                        </strong>

                        <small>
                            Manage mentor sessions,
                            goals and progress.
                        </small>
                    </div>

                </div>

                <div class="eh-auth-benefit">

                    <span>
                        <i class="fas fa-briefcase"></i>
                    </span>

                    <div>
                        <strong>
                            Career opportunities
                        </strong>

                        <small>
                            Build your resume and apply
                            for opportunities.
                        </small>
                    </div>

                </div>

            </div>

        </div>

    </section>


    <section class="eh-auth-form-side">

        <div class="eh-auth-form-inner">

            <span class="eh-auth-badge eh-auth-badge-light">
                <i class="fas fa-circle-user"></i>
                Participant access
            </span>

            <div class="eh-auth-heading">

                <h2>
                    Participant Sign In
                </h2>

                <p>
                    Use the email address linked to
                    your ElevateHer360 account.
                </p>

            </div>

            @include('partials.form-feedback')

            <form
                method="POST"
                action="{{ route('login.attempt') }}"
                class="eh-auth-form"
            >

                @csrf

                <div class="eh-auth-field">

                    <label for="email">
                        Email address
                        <span>*</span>
                    </label>

                    <div class="eh-auth-input-wrap">

                        <i class="fas fa-envelope"></i>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="e.g. name@example.com"
                            autocomplete="email"
                            required
                            autofocus
                        >

                    </div>

                    <small>
                        Enter the email used when you
                        registered for ElevateHer360.
                    </small>

                    @error('email')
                        <div class="eh-auth-field-error">
                            <i class="fas fa-circle-exclamation"></i>
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div class="eh-auth-field">

                    <label for="participant_password">
                        Password
                        <span>*</span>
                    </label>

                    <div class="eh-auth-input-wrap">

                        <i class="fas fa-lock"></i>

                        <input
                            id="participant_password"
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="eh-auth-password-toggle"
                            data-password-toggle="participant_password"
                            aria-label="Show or hide password"
                        >
                            <i class="fas fa-eye"></i>
                        </button>

                    </div>

                    @error('password')
                        <div class="eh-auth-field-error">
                            <i class="fas fa-circle-exclamation"></i>
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <label class="eh-auth-remember">

                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                        @checked(old('remember'))
                    >

                    <span>
                        <strong>
                            Remember me
                        </strong>

                        <small>
                            Keep this account signed in
                            on this device.
                        </small>
                    </span>

                </label>


                <button
                    type="submit"
                    class="eh-auth-submit"
                >
                    <i class="fas fa-right-to-bracket"></i>
                    Sign In
                </button>

            </form>


            <div class="eh-auth-bottom-link">

                <span>
                    Don't have an account?
                </span>

                <a href="{{ route('register') }}">
                    Create participant account
                </a>

            </div>

        </div>

    </section>

</div>

@endsection