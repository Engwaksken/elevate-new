@extends('layouts.app')
@section('title','Reset Password | ElevateHer360')
@section('content')

<div class="auth-layout">
    <section class="auth-side">
        <div class="gold-line"></div>
        <h1>Create a new password.</h1>
        <p>Choose a strong password to secure your ElevateHer360 account.</p>
    </section>

    <section class="auth-panel">
        <h2>Reset Password</h2>
        <p class="subtitle">Your new password must contain at least 8 characters, mixed case and a number.</p>

        @if($errors->any())
            <div class="alert alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label for="reset_email" class="required">Email address</label>
                <input
                    id="reset_email"
                    type="email"
                    name="email"
                    value="{{ old('email',$email) }}"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-group">
                <label for="reset_password" class="required">New password</label>
                <div class="password-wrap">
                    <input
                        id="reset_password"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        required
                    >
                    <button
                        type="button"
                        class="password-toggle"
                        data-password-toggle="reset_password"
                        aria-label="Show or hide password"
                    >
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="reset_password_confirmation" class="required">Confirm new password</label>
                <div class="password-wrap">
                    <input
                        id="reset_password_confirmation"
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        required
                    >
                    <button
                        type="button"
                        class="password-toggle"
                        data-password-toggle="reset_password_confirmation"
                        aria-label="Show or hide password"
                    >
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-key"></i>
                Reset Password
            </button>
        </form>
    </section>
</div>

@endsection
