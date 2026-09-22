@extends('layouts.app')

@section('title', 'Create Participant Account | ElevateHer360')

@section('content')

<div class="auth-layout">

    <section class="auth-side">

        <div class="gold-line"></div>

        <h1>
            Start your ElevateHer360 journey.
        </h1>

        <p>
            Create one participant account for learning,
            mentorship, career support, jobs, resources and
            progress tracking.
        </p>

        <ul>
            <li>One profile across all services</li>
            <li>Track courses and certificates</li>
            <li>Access mentorship</li>
            <li>Build your career profile</li>
            <li>Apply for jobs and opportunities</li>
        </ul>

    </section>

    <section class="auth-panel">

        <div class="registration-heading">

            <div>
                <h2>Create Participant Account</h2>

                <p class="subtitle">
                    Complete the steps below to set up your account.
                </p>
            </div>

            <div class="registration-progress-label">
                <span id="registrationStepLabel">
                    Step 1 of 4
                </span>
            </div>

        </div>

        <div class="registration-progress">

            <div
                class="registration-progress-bar"
                id="registrationProgressBar"
                style="width:25%"
            ></div>

        </div>

        <div class="registration-tabs">

            <button
                type="button"
                class="registration-tab active"
                data-step="1"
            >
                <span class="tab-number">1</span>
                <span class="tab-text">Personal</span>
            </button>

            <button
                type="button"
                class="registration-tab"
                data-step="2"
            >
                <span class="tab-number">2</span>
                <span class="tab-text">Location</span>
            </button>

            <button
                type="button"
                class="registration-tab"
                data-step="3"
            >
                <span class="tab-number">3</span>
                <span class="tab-text">Career</span>
            </button>

            <button
                type="button"
                class="registration-tab"
                data-step="4"
            >
                <span class="tab-number">4</span>
                <span class="tab-text">Security</span>
            </button>

        </div>

        <form
            method="POST"
            action="{{ route('register.store') }}"
            id="registrationForm"
        >

            @csrf

            {{-- Step 1 --}}
            <div
                class="registration-step active"
                data-step-panel="1"
            >

                <div class="step-heading">
                    <h3>Personal Information</h3>
                    <p>
                        Tell us who you are.
                    </p>
                </div>

                <div class="grid">

                    <div class="form-group">

                        <label
                            for="surname"
                            class="required"
                        >
                            Surname
                        </label>

                        <input
                            id="surname"
                            name="surname"
                            value="{{ old('surname') }}"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label
                            for="given_name"
                            class="required"
                        >
                            Given name
                        </label>

                        <input
                            id="given_name"
                            name="given_name"
                            value="{{ old('given_name') }}"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="other_name">
                            Other name
                        </label>

                        <input
                            id="other_name"
                            name="other_name"
                            value="{{ old('other_name') }}"
                        >

                    </div>

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
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="phone">
                            Phone number
                        </label>

                        <input
                            id="phone"
                            name="phone"
                            value="{{ old('phone') }}"
                        >

                    </div>

                    <div class="form-group">

                        <label for="date_of_birth">
                            Date of birth
                        </label>

                        <input
                            id="date_of_birth"
                            type="date"
                            name="date_of_birth"
                            value="{{ old('date_of_birth') }}"
                        >

                    </div>

                    <div class="form-group">

                        <label for="gender">
                            Gender
                        </label>

                        <select
                            id="gender"
                            name="gender"
                        >

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

                </div>

            </div>

            {{-- Step 2 --}}
            <div
                class="registration-step"
                data-step-panel="2"
            >

                <div class="step-heading">
                    <h3>Location & Inclusion</h3>
                    <p>
                        Help us understand where you are based.
                    </p>
                </div>

                <div class="grid">

                    <div class="form-group">

                        <label for="branch_id">
                            Branch
                        </label>

                        <select
                            id="branch_id"
                            name="branch_id"
                        >

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

                        <label for="country">
                            Country
                        </label>

                        <input
                            id="country"
                            name="country"
                            value="{{ old('country', 'Uganda') }}"
                        >

                    </div>

                    <div class="form-group">

                        <label for="district">
                            District
                        </label>

                        <input
                            id="district"
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

            </div>

            {{-- Step 3 --}}
            <div
                class="registration-step"
                data-step-panel="3"
            >

                <div class="step-heading">
                    <h3>Career Interests</h3>
                    <p>
                        Tell us what you are interested in learning or pursuing.
                    </p>
                </div>

                <div class="form-group">

                    <label for="career_interests">
                        Career interests
                    </label>

                    <textarea
                        id="career_interests"
                        name="career_interests"
                        placeholder="Example: software development, digital marketing, data analysis..."
                    >{{ old('career_interests') }}</textarea>

                </div>

            </div>

            {{-- Step 4 --}}
            <div
                class="registration-step"
                data-step-panel="4"
            >

                <div class="step-heading">
                    <h3>Account Security</h3>
                    <p>
                        Create your password and confirm your consent.
                    </p>
                </div>

                <div class="grid">

                    <div class="form-group">

                        <label
                            for="registration_password"
                            class="required"
                        >
                            Password
                        </label>

                        <div class="password-wrap">

                            <input
                                id="registration_password"
                                type="password"
                                name="password"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-password-toggle="registration_password"
                            >
                                <i class="fas fa-eye"></i>
                            </button>

                        </div>

                    </div>

                    <div class="form-group">

                        <label
                            for="registration_password_confirmation"
                            class="required"
                        >
                            Confirm password
                        </label>

                        <div class="password-wrap">

                            <input
                                id="registration_password_confirmation"
                                type="password"
                                name="password_confirmation"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-password-toggle="registration_password_confirmation"
                            >
                                <i class="fas fa-eye"></i>
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
                        @checked(old('privacy_policy'))
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
                        @checked(old('terms'))
                        required
                    >

                    <label for="terms">
                        I accept the Terms and Conditions
                    </label>

                </div>

            </div>

            <div class="registration-actions">

                <button
                    type="button"
                    id="registrationPrevious"
                    class="btn btn-outline"
                    disabled
                >
                    <i class="fas fa-arrow-left"></i>
                    Previous
                </button>

                <button
                    type="button"
                    id="registrationNext"
                    class="btn btn-primary"
                >
                    Next
                    <i class="fas fa-arrow-right"></i>
                </button>

                <button
                    type="submit"
                    id="registrationSubmit"
                    class="btn btn-primary"
                    style="display:none"
                >
                    <i class="fas fa-user-check"></i>
                    Create Account
                </button>

            </div>

        </form>

        <div class="auth-links">

            <p>
                Already registered?

                <a href="{{ route('login') }}">
                    Sign in
                </a>
            </p>

        </div>

    </section>

</div>

@endsection


@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {

    let currentStep = 1;
    const totalSteps = 4;

    const tabs = document.querySelectorAll('.registration-tab');
    const panels = document.querySelectorAll('.registration-step');

    const previousButton =
        document.getElementById('registrationPrevious');

    const nextButton =
        document.getElementById('registrationNext');

    const submitButton =
        document.getElementById('registrationSubmit');

    const progressBar =
        document.getElementById('registrationProgressBar');

    const stepLabel =
        document.getElementById('registrationStepLabel');

    function validateCurrentStep() {

        const currentPanel =
            document.querySelector(
                '[data-step-panel="' + currentStep + '"]'
            );

        const requiredFields =
            currentPanel.querySelectorAll('[required]');

        for (const field of requiredFields) {

            if (!field.checkValidity()) {
                field.reportValidity();
                return false;
            }

        }

        return true;
    }

    function updateRegistrationStep() {

        tabs.forEach(function (tab) {

            const step =
                Number(tab.dataset.step);

            tab.classList.toggle(
                'active',
                step === currentStep
            );

            tab.classList.toggle(
                'completed',
                step < currentStep
            );

        });

        panels.forEach(function (panel) {

            panel.classList.toggle(
                'active',
                Number(panel.dataset.stepPanel) === currentStep
            );

        });

        previousButton.disabled =
            currentStep === 1;

        nextButton.style.display =
            currentStep === totalSteps
                ? 'none'
                : 'inline-flex';

        submitButton.style.display =
            currentStep === totalSteps
                ? 'inline-flex'
                : 'none';

        progressBar.style.width =
            ((currentStep / totalSteps) * 100) + '%';

        stepLabel.textContent =
            'Step ' + currentStep + ' of ' + totalSteps;
    }

    nextButton.addEventListener('click', function () {

        if (!validateCurrentStep()) {
            return;
        }

        if (currentStep < totalSteps) {
            currentStep++;
            updateRegistrationStep();
        }

    });

    previousButton.addEventListener('click', function () {

        if (currentStep > 1) {
            currentStep--;
            updateRegistrationStep();
        }

    });

    tabs.forEach(function (tab) {

        tab.addEventListener('click', function () {

            const requestedStep =
                Number(tab.dataset.step);

            if (requestedStep <= currentStep) {
                currentStep = requestedStep;
                updateRegistrationStep();
            }

        });

    });

    updateRegistrationStep();

});
</script>

@endpush