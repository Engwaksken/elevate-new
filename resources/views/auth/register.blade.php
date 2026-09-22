@extends('layouts.app')

@section('title', 'Create Account | ElevateHer360')

@section('content')

<div class="auth-layout">

    <section class="auth-side">

        <div class="gold-line"></div>

        <h1>Start your ElevateHer360 journey.</h1>

        <p>
            Create one participant account for learning,
            mentorship, careers, jobs, resources and progress
            tracking.
        </p>

        <ul>
            <li>One profile across all ElevateHer360 services</li>
            <li>Track courses and certificates</li>
            <li>Access mentorship</li>
            <li>Build your career profile</li>
            <li>Apply for jobs and opportunities</li>
        </ul>

    </section>

    <section class="auth-panel">

        <h2>Create Participant Account</h2>

        <p class="subtitle">
            Please provide your information below.
        </p>

        <form method="POST" action="{{ route('register.store') }}">
            @csrf

            <div class="grid">

                <div class="form-group">
                    <label>Surname</label>
                    <input
                        name="surname"
                        value="{{ old('surname') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Given name</label>
                    <input
                        name="given_name"
                        value="{{ old('given_name') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Other name</label>
                    <input
                        name="other_name"
                        value="{{ old('other_name') }}"
                    >
                </div>

                <div class="form-group">
                    <label>Email address</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Phone number</label>
                    <input
                        name="phone"
                        value="{{ old('phone') }}"
                    >
                </div>

                <div class="form-group">
                    <label>Date of birth</label>
                    <input
                        type="date"
                        name="date_of_birth"
                        value="{{ old('date_of_birth') }}"
                    >
                </div>

                <div class="form-group">

                    <label>Gender</label>

                    <select name="gender">

                        <option value="">
                            Select gender
                        </option>

                        <option
                            value="female"
                            @selected(old('gender') === 'female')
                        >
                            Female
                        </option>

                        <option
                            value="male"
                            @selected(old('gender') === 'male')
                        >
                            Male
                        </option>

                        <option
                            value="other"
                            @selected(old('gender') === 'other')
                        >
                            Other
                        </option>

                        <option
                            value="prefer_not_to_say"
                            @selected(old('gender') === 'prefer_not_to_say')
                        >
                            Prefer not to say
                        </option>

                    </select>

                </div>

                <div class="form-group">

                    <label>Branch</label>

                    <select name="branch_id">

                        <option value="">
                            Select branch
                        </option>

                        @foreach($branches as $branch)

                            <option
                                value="{{ $branch->id }}"
                                @selected(old('branch_id') == $branch->id)
                            >
                                {{ $branch->name }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <div class="form-group">
                    <label>Country</label>
                    <input
                        name="country"
                        value="{{ old('country', 'Uganda') }}"
                    >
                </div>

                <div class="form-group">
                    <label>District</label>
                    <input
                        name="district"
                        value="{{ old('district') }}"
                    >
                </div>

            </div>

            <div class="checkbox-row">

                <input
                    id="is_pwd"
                    type="checkbox"
                    name="is_pwd"
                    value="1"
                    @checked(old('is_pwd'))
                >

                <label for="is_pwd">
                    I am a person with a disability
                </label>

            </div>

            <div class="form-group">

                <label>Career interests</label>

                <textarea
                    name="career_interests"
                    placeholder="Tell us about your career interests"
                >{{ old('career_interests') }}</textarea>

            </div>

            <div class="grid">

                <div class="form-group">

                    <label>Password</label>

                    <div class="password-wrap">

                        <input
                            id="registration_password"
                            type="password"
                            name="password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-password-toggle="registration_password"
                        >
                            Show
                        </button>

                    </div>

                </div>

                <div class="form-group">

                    <label>Confirm password</label>

                    <div class="password-wrap">

                        <input
                            id="registration_password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-password-toggle="registration_password_confirmation"
                        >
                            Show
                        </button>

                    </div>

                </div>

            </div>

            <div class="checkbox-row">

                <input
                    id="privacy_policy"
                    type="checkbox"
                    name="privacy_policy"
                    value="1"
                    required
                >

                <label for="privacy_policy">
                    I accept the Privacy Policy
                </label>

            </div>

            <div class="checkbox-row">

                <input
                    id="terms"
                    type="checkbox"
                    name="terms"
                    value="1"
                    required
                >

                <label for="terms">
                    I accept the Terms and Conditions
                </label>

            </div>

            <button
                type="submit"
                class="btn btn-primary btn-block"
            >
                Create Account
            </button>

        </form>

        <div class="auth-links">

            Already registered?

            <a href="{{ route('login') }}">
                Sign in
            </a>

        </div>

    </section>

</div>

@endsection