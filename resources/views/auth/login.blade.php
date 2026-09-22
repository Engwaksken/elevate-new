@extends('layouts.app')

@section('title', 'Participant Login | ElevateHer360')

@section('content')

<div class="auth-layout">

    <section class="auth-side">

        <div class="gold-line"></div>

        <h1>
            Welcome back to ElevateHer360.
        </h1>

        <p>
            Sign in with your participant account
            to continue your learning, mentorship,
            career and employment journey.
        </p>

        <ul>
            <li>
                Continue courses and assessments
            </li>

            <li>
                Connect with your mentor
            </li>

            <li>
                Build and manage your resume
            </li>

            <li>
                Apply for opportunities
            </li>

            <li>
                Track your progress and outcomes
            </li>
        </ul>

    </section>


    <section class="auth-panel">

        <h2>
            Participant Sign In
        </h2>

        <p class="subtitle">
            Use the email address linked to your
            ElevateHer360 participant account.
        </p>

        <form
            method="POST"
            action="{{ route('login.attempt') }}"
        >

            @csrf

            <div class="form-group">

                <label
                    for="email"
                    class="required"
                >
                    Email address
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    required
                    autofocus
                >

            </div>


            <div class="form-group">

                <label
                    for="participant_password"
                    class="required"
                >
                    Password
                </label>

                <div class="password-wrap">

                    <input
                        id="participant_password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        data-password-toggle="participant_password"
                    >
                        Show
                    </button>

                </div>

            </div>


            <div class="checkbox-row">

                <input
                    id="remember"
                    type="checkbox"
                    name="remember"
                    value="1"
                    @checked(old('remember'))
                >

                <label for="remember">
                    Remember me
                </label>

            </div>


            <button
                type="submit"
                class="btn btn-primary btn-block"
            >
                Sign In
            </button>

        </form>


        <div class="auth-links">

            <p>
                Don't have an account?

                <a href="{{ route('register') }}">
                    Create participant account
                </a>
            </p>

            <p>
                Are you WITU staff?

                <a href="{{ route('admin.login') }}">
                    Use the staff portal
                </a>
            </p>

        </div>

    </section>

</div>

@endsection