@extends('layouts.app')

@section('title', 'Staff Login | ElevateHer360')

@section('content')

<div class="auth-layout">

    <section class="auth-side">

        <div class="gold-line"></div>

        <span class="staff-badge">
            WITU STAFF PORTAL
        </span>

        <h1>
            Manage programmes and participant impact.
        </h1>

        <p>
            Secure access for authorised WITU staff,
            instructors, programme teams, M&E,
            operations and administrators.
        </p>

        <ul>
            <li>
                Programme and cohort management
            </li>

            <li>
                Learning and mentorship oversight
            </li>

            <li>
                Workplans and M&E reporting
            </li>

            <li>
                HR, assets and procurement
            </li>

            <li>
                Executive reporting and administration
            </li>
        </ul>

    </section>


    <section class="auth-panel">

        <span class="staff-badge">
            Authorised staff only
        </span>

        <h2>
            Staff Sign In
        </h2>

        <p class="subtitle">
            Enter your authorised WITU staff credentials.
        </p>

        <form
            method="POST"
            action="{{ route('admin.login.attempt') }}"
        >

            @csrf


            <div class="form-group">

                <label
                    for="staff_email"
                    class="required"
                >
                    Work email
                </label>

                <input
                    id="staff_email"
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
                    for="staff_password"
                    class="required"
                >
                    Password
                </label>

                <div class="password-wrap">

                    <input
                        id="staff_password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        data-password-toggle="staff_password"
                    >
                        Show
                    </button>

                </div>

            </div>


            <div class="checkbox-row">

                <input
                    id="staff_remember"
                    type="checkbox"
                    name="remember"
                    value="1"
                    @checked(old('remember'))
                >

                <label for="staff_remember">
                    Remember me
                </label>

            </div>


            <button
                type="submit"
                class="btn btn-primary btn-block"
            >
                Sign In to Staff Portal
            </button>

        </form>


        <div class="auth-links">

            <p>
                Participant?

                <a href="{{ route('login') }}">
                    Use participant login
                </a>
            </p>

            <p>
                <a href="{{ route('home') }}">
                    Return to ElevateHer360
                </a>
            </p>

        </div>

    </section>

</div>

@endsection