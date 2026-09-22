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
            programme teams and administrators.
        </p>

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
                    placeholder="e.g. name@witu.org"
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
                        placeholder="Enter your staff password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        data-password-toggle="staff_password"
                        aria-label="Show or hide password"
                    >
                        <i class="fas fa-eye"></i>
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
                <i class="fas fa-shield-halved"></i>
                Sign In
            </button>

        </form>

    </section>

</div>

@endsection