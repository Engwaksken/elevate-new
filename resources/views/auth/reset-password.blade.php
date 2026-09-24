@extends('layouts.app')

@section('title','Reset Password | ElevateHer360')

@section('content')

<div class="eh-auth-shell">

    <section class="eh-auth-hero">

        <div class="eh-auth-hero-inner">

            <div class="eh-auth-gold-line"></div>

            <span class="eh-auth-badge">
                <i class="fas fa-shield-halved"></i>
                SECURE PASSWORD RESET
            </span>

            <h1>
                Create your new password.
            </h1>

            <p>
                Choose a strong password to protect
                your ElevateHer360 account.
            </p>

        </div>

    </section>


    <section class="eh-auth-form-side">

        <div class="eh-auth-form-inner">

            <div class="eh-auth-heading">

                <h2>
                    Reset Password
                </h2>

                <p>
                    Enter your new account password.
                </p>

            </div>


            @include('partials.form-feedback')


            <form
                method="POST"
                action="{{ route('password.update') }}"
                class="eh-auth-form"
            >

                @csrf

                <input
                    type="hidden"
                    name="token"
                    value="{{ $token }}"
                >


                <div class="eh-auth-field">

                    <label for="email">
                        Email address
                    </label>

                    <div class="eh-auth-input-wrap">

                        <i class="fas fa-envelope"></i>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email', $email) }}"
                            required
                        >

                    </div>

                </div>


                <div class="eh-auth-field">

                    <label for="password">
                        New password
                        <span>*</span>
                    </label>

                    <div class="eh-auth-input-wrap">

                        <i class="fas fa-lock"></i>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Enter new password"
                            required
                        >

                        <button
                            type="button"
                            class="eh-auth-password-toggle"
                            data-password-toggle="password"
                        >
                            <i class="fas fa-eye"></i>
                        </button>

                    </div>

                </div>


                <div class="eh-auth-field">

                    <label for="password_confirmation">
                        Confirm password
                        <span>*</span>
                    </label>

                    <div class="eh-auth-input-wrap">

                        <i class="fas fa-lock"></i>

                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            placeholder="Repeat new password"
                            required
                        >

                        <button
                            type="button"
                            class="eh-auth-password-toggle"
                            data-password-toggle="password_confirmation"
                        >
                            <i class="fas fa-eye"></i>
                        </button>

                    </div>

                </div>


                <button
                    type="submit"
                    class="eh-auth-submit"
                >
                    <i class="fas fa-floppy-disk"></i>
                    Reset Password
                </button>

            </form>

        </div>

    </section>

</div>

@endsection