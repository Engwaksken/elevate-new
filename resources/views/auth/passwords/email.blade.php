@extends('layouts.app')
@section('title','Forgot Password | ElevateHer360')
@section('content')

<div class="auth-layout">
    <section class="auth-side">
        <div class="gold-line"></div>
        <h1>Reset your ElevateHer360 password.</h1>
        <p>Enter the email address linked to your account. We will send you a secure password reset link.</p>
    </section>

    <section class="auth-panel">
        <h2>Forgot Password</h2>
        <p class="subtitle">Use the email address registered on your ElevateHer360 account.</p>

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

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="form-group">
                <label for="reset_email" class="required">Email address</label>
                <input
                    id="reset_email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="e.g. name@example.com"
                    autocomplete="email"
                    required
                    autofocus
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-paper-plane"></i>
                Send Reset Link
            </button>
        </form>

        <div class="auth-links">
            <p><a href="{{ route('login') }}">Back to participant sign in</a></p>
        </div>
    </section>
</div>

@endsection
