@extends('layouts.app')

@section('title','Create Participant Account | ElevateHer360')

@section('content')

<div class="eh-register-shell">

    {{-- LEFT PANEL --}}
    <section class="eh-register-hero">

        <div class="eh-register-hero-inner">

            <div class="eh-register-gold-line"></div>

            <span class="eh-register-badge">
                <i class="fas fa-user-plus"></i>
                PARTICIPANT REGISTRATION
            </span>

            <h1>
                Start your ElevateHer360 journey.
            </h1>

            <p>
                Create one participant account for learning,
                mentorship, career support, jobs, resources
                and progress tracking.
            </p>

            <div class="eh-register-benefits">

                <div class="eh-register-benefit">
                    <span>
                        <i class="fas fa-graduation-cap"></i>
                    </span>

                    <div>
                        <strong>
                            Learn and grow
                        </strong>

                        <small>
                            Track courses, certificates
                            and development progress.
                        </small>
                    </div>
                </div>

                <div class="eh-register-benefit">
                    <span>
                        <i class="fas fa-user-group"></i>
                    </span>

                    <div>
                        <strong>
                            Access mentorship
                        </strong>

                        <small>
                            Connect with mentors and
                            manage your growth goals.
                        </small>
                    </div>
                </div>

                <div class="eh-register-benefit">
                    <span>
                        <i class="fas fa-briefcase"></i>
                    </span>

                    <div>
                        <strong>
                            Build your career
                        </strong>

                        <small>
                            Apply for jobs and create
                            a professional career profile.
                        </small>
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

                    <span class="eh-register-step-count">
                        Step
                        <strong id="registerStepNumber">
                            1
                        </strong>
                        of 4
                    </span>

                    <h2>
                        Create Participant Account
                    </h2>

                    <p>
                        Complete the steps below to
                        set up your account.
                    </p>

                </div>

            </div>


            @include('partials.form-feedback')


            {{-- PROGRESS --}}
            <div class="eh-register-progress">

                <button
                    type="button"
                    class="eh-register-progress-item active"
                    data-register-step-button="1"
                >
                    <span>1</span>
                    <small>Personal</small>
                </button>

                <button
                    type="button"
                    class="eh-register-progress-item"
                    data-register-step-button="2"
                >
                    <span>2</span>
                    <small>Location</small>
                </button>

                <button
                    type="button"
                    class="eh-register-progress-item"
                    data-register-step-button="3"
                >
                    <span>3</span>
                    <small>Career</small>
                </button>

                <button
                    type="button"
                    class="eh-register-progress-item"
                    data-register-step-button="4"
                >
                    <span>4</span>
                    <small>Security</small>
                </button>

            </div>


            <form
                method="POST"
                action="{{ route('register.store') }}"
                id="registrationForm"
                class="eh-register-form"
            >

                @csrf


                {{-- ==================================================
                     STEP 1
                     ================================================== --}}

                <section
                    class="eh-register-step active"
                    data-register-step="1"
                >

                    <div class="eh-register-step-heading">

                        <h3>
                            Personal Information
                        </h3>

                        <p>
                            Tell us who you are.
                        </p>

                    </div>


                    <div class="eh-register-grid">

                        <div class="eh-register-field">

                            <label for="surname">
                                Surname
                                <span>*</span>
                            </label>

                            <input
                                id="surname"
                                type="text"
                                name="surname"
                                value="{{ old('surname') }}"
                                placeholder="e.g. Nansubuga"
                                required
                            >

                            <small>
                                Enter your family or surname.
                            </small>

                        </div>


                        <div class="eh-register-field">

                            <label for="given_name">
                                Given name
                                <span>*</span>
                            </label>

                            <input
                                id="given_name"
                                type="text"
                                name="given_name"
                                value="{{ old('given_name') }}"
                                placeholder="e.g. Sarah"
                                required
                            >

                            <small>
                                Enter your first or given name.
                            </small>

                        </div>


                        <div class="eh-register-field">

                            <label for="other_name">
                                Other name
                            </label>

                            <input
                                id="other_name"
                                type="text"
                                name="other_name"
                                value="{{ old('other_name') }}"
                                placeholder="Enter other name if applicable"
                            >

                        </div>


                        <div class="eh-register-field">

                            <label for="email">
                                Email address
                                <span>*</span>
                            </label>

                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="e.g. name@example.com"
                                required
                            >

                            <small>
                                This email will be used to sign in.
                            </small>

                        </div>


                        <div class="eh-register-field">

                            <label for="phone">
                                Phone number
                            </label>

                            <input
                                id="phone"
                                type="text"
                                name="phone"
                                value="{{ old('phone') }}"
                                placeholder="e.g. +256 700 000000"
                            >

                        </div>


                        <div class="eh-register-field">

                            <label for="date_of_birth">
                                Date of birth
                            </label>

                            <input
                                id="date_of_birth"
                                type="date"
                                name="date_of_birth"
                                value="{{ old('date_of_birth') }}"
                            >

                            <small>
                                Select your date of birth.
                            </small>

                        </div>


                        <div class="eh-register-field">

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

                </section>


                {{-- ==================================================
                     STEP 2
                     ================================================== --}}

                <section
                    class="eh-register-step"
                    data-register-step="2"
                >

                    <div class="eh-register-step-heading">

                        <h3>
                            Location & Accessibility
                        </h3>

                        <p>
                            Help us understand your location
                            and accessibility needs.
                        </p>

                    </div>


                    <div class="eh-register-grid">

                        <div class="eh-register-field">

                            <label for="country">
                                Country
                            </label>

                            <input
                                id="country"
                                type="text"
                                name="country"
                                value="{{ old('country', 'Uganda') }}"
                                placeholder="e.g. Uganda"
                            >

                        </div>


                        <div class="eh-register-field">

                            <label for="district">
                                District
                            </label>

                            <input
                                id="district"
                                type="text"
                                name="district"
                                value="{{ old('district') }}"
                                placeholder="e.g. Kampala"
                            >

                        </div>


                        <div class="eh-register-field eh-register-field-full">

                            <label for="location">
                                Location
                            </label>

                            <input
                                id="location"
                                type="text"
                                name="location"
                                value="{{ old('location') }}"
                                placeholder="e.g. Bukoto, Kampala"
                            >

                            <small>
                                Enter your town, city or community.
                            </small>

                        </div>

                    </div>


                    <div class="eh-register-pwd">

                        <label class="eh-register-checkbox">

                            <input
                                id="register_is_pwd"
                                type="checkbox"
                                name="is_pwd"
                                value="1"
                                @checked(old('is_pwd'))
                            >

                            <span>
                                <strong>
                                    Person with Disability (PWD)
                                </strong>

                                <small>
                                    Select this if you identify as
                                    a person with disability.
                                </small>
                            </span>

                        </label>


                        <div
                            id="registerPwdDetails"
                            class="eh-register-pwd-details"
                        >

                            <h4>
                                Select disability type
                            </h4>

                            <p>
                                You may select more than one.
                            </p>


                            <div class="eh-register-check-grid">

                                @foreach([
                                    'visual' => 'Visual impairment',
                                    'hearing' => 'Hearing impairment',
                                    'physical' => 'Physical disability',
                                    'intellectual' => 'Intellectual disability',
                                    'psychosocial' => 'Psychosocial disability',
                                    'speech' => 'Speech / communication disability',
                                    'multiple' => 'Multiple disabilities',
                                    'other' => 'Other',
                                ] as $value => $label)

                                    <label class="eh-register-check-option">

                                        <input
                                            type="checkbox"
                                            name="disability_types[]"
                                            value="{{ $value }}"
                                            @checked(
                                                in_array(
                                                    $value,
                                                    old('disability_types', [])
                                                )
                                            )
                                        >

                                        <span>
                                            {{ $label }}
                                        </span>

                                    </label>

                                @endforeach

                            </div>


                            <div
                                id="registerPwdOther"
                                class="eh-register-field eh-register-pwd-other"
                            >

                                <label for="disability_other">
                                    Specify other disability
                                </label>

                                <input
                                    id="disability_other"
                                    type="text"
                                    name="disability_other"
                                    value="{{ old('disability_other') }}"
                                    placeholder="Please specify"
                                >

                            </div>

                        </div>

                    </div>

                </section>


                {{-- ==================================================
                     STEP 3
                     ================================================== --}}

                <section
                    class="eh-register-step"
                    data-register-step="3"
                >

                    <div class="eh-register-step-heading">

                        <h3>
                            Career Information
                        </h3>

                        <p>
                            Tell us about your education
                            and career interests.
                        </p>

                    </div>


                    <div class="eh-register-grid">

                        <div class="eh-register-field">

                            <label for="education_level">
                                Education level
                            </label>

                            <input
                                id="education_level"
                                type="text"
                                name="education_level"
                                value="{{ old('education_level') }}"
                                placeholder="e.g. Bachelor's degree"
                            >

                        </div>


                        <div class="eh-register-field">

                            <label for="employment_status">
                                Employment status
                            </label>

                            <select
                                id="employment_status"
                                name="employment_status"
                            >

                                <option value="">
                                    Select status
                                </option>

                                @foreach([
                                    'student' => 'Student',
                                    'employed' => 'Employed',
                                    'self_employed' => 'Self-employed',
                                    'unemployed' => 'Unemployed',
                                ] as $value => $label)

                                    <option
                                        value="{{ $value }}"
                                        @selected(
                                            old('employment_status') === $value
                                        )
                                    >
                                        {{ $label }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        <div class="eh-register-field eh-register-field-full">

                            <label for="career_interests">
                                Career interests
                            </label>

                            <textarea
                                id="career_interests"
                                name="career_interests"
                                rows="5"
                                placeholder="e.g. Software development, data analysis, entrepreneurship..."
                            >{{ old('career_interests') }}</textarea>

                            <small>
                                Enter one or more areas you are interested in.
                            </small>

                        </div>

                    </div>

                </section>


                {{-- ==================================================
                     STEP 4
                     ================================================== --}}

                <section
                    class="eh-register-step"
                    data-register-step="4"
                >

                    <div class="eh-register-step-heading">

                        <h3>
                            Account Security
                        </h3>

                        <p>
                            Set your password and confirm
                            your account preferences.
                        </p>

                    </div>


                    <div class="eh-register-grid">

                        <div class="eh-register-field">

                            <label for="password">
                                Password
                                <span>*</span>
                            </label>

                            <div class="eh-register-password">

                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    placeholder="Create a strong password"
                                    required
                                >

                                <button
                                    type="button"
                                    data-password-toggle="password"
                                    aria-label="Show or hide password"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>

                            </div>

                            <small>
                                Use at least 8 characters.
                            </small>

                        </div>


                        <div class="eh-register-field">

                            <label for="password_confirmation">
                                Confirm password
                                <span>*</span>
                            </label>

                            <div class="eh-register-password">

                                <input
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    placeholder="Repeat your password"
                                    required
                                >

                                <button
                                    type="button"
                                    data-password-toggle="password_confirmation"
                                    aria-label="Show or hide password"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>

                            </div>

                        </div>


                        <div class="eh-register-field">

                            <label for="preferred_language">
                                Preferred language
                            </label>

                            <select
                                id="preferred_language"
                                name="preferred_language"
                            >

                                <option value="en">
                                    English
                                </option>

                                <option value="lg">
                                    Luganda
                                </option>

                                <option value="sw">
                                    Kiswahili
                                </option>

                            </select>

                        </div>


                        <div class="eh-register-field eh-register-field-full">

                            <label class="eh-register-checkbox">

                                <input
                                    type="checkbox"
                                    name="terms"
                                    value="1"
                                    required
                                >

                                <span>
                                    <strong>
                                        I agree to the Terms of Use
                                        and Privacy Policy.
                                    </strong>

                                    <small>
                                        You must agree before creating
                                        your account.
                                    </small>
                                </span>

                            </label>

                        </div>

                    </div>

                </section>


                {{-- NAVIGATION --}}
                <div class="eh-register-actions">

                    <button
                        type="button"
                        id="registerPrevious"
                        class="eh-register-btn eh-register-btn-outline"
                    >
                        <i class="fas fa-arrow-left"></i>
                        Previous
                    </button>


                    <button
                        type="button"
                        id="registerNext"
                        class="eh-register-btn eh-register-btn-primary"
                    >
                        Next
                        <i class="fas fa-arrow-right"></i>
                    </button>


                    <button
                        type="submit"
                        id="registerSubmit"
                        class="eh-register-btn eh-register-btn-primary"
                    >
                        <i class="fas fa-user-check"></i>
                        Create Account
                    </button>

                </div>

            </form>


            <div class="eh-register-login-link">

                <span>
                    Already registered?
                </span>

                <a href="{{ route('login') }}">
                    Sign in
                </a>

            </div>

        </div>

    </section>

</div>

@endsection


@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {

    let currentStep = 1;

    const totalSteps = 4;

    const steps =
        document.querySelectorAll(
            '[data-register-step]'
        );

    const progress =
        document.querySelectorAll(
            '[data-register-step-button]'
        );

    const previous =
        document.getElementById(
            'registerPrevious'
        );

    const next =
        document.getElementById(
            'registerNext'
        );

    const submit =
        document.getElementById(
            'registerSubmit'
        );

    const stepNumber =
        document.getElementById(
            'registerStepNumber'
        );


    function showStep(step) {

        currentStep = step;

        steps.forEach(function (panel) {

            panel.classList.toggle(
                'active',
                Number(panel.dataset.registerStep)
                    === step
            );

        });


        progress.forEach(function (item) {

            const itemStep =
                Number(
                    item.dataset.registerStepButton
                );

            item.classList.toggle(
                'active',
                itemStep === step
            );

            item.classList.toggle(
                'complete',
                itemStep < step
            );

        });


        if (stepNumber) {
            stepNumber.textContent = step;
        }


        previous.style.display =
            step === 1
                ? 'none'
                : 'inline-flex';


        next.style.display =
            step === totalSteps
                ? 'none'
                : 'inline-flex';


        submit.style.display =
            step === totalSteps
                ? 'inline-flex'
                : 'none';

    }


    previous.addEventListener(
        'click',
        function () {

            if (currentStep > 1) {
                showStep(currentStep - 1);
            }

        }
    );


    next.addEventListener(
        'click',
        function () {

            const active =
                document.querySelector(
                    `[data-register-step="${currentStep}"]`
                );

            const required =
                active.querySelectorAll(
                    '[required]'
                );

            let valid = true;

            required.forEach(function (input) {

                if (!input.checkValidity()) {

                    input.reportValidity();

                    valid = false;
                }

            });


            if (
                valid &&
                currentStep < totalSteps
            ) {
                showStep(currentStep + 1);
            }

        }
    );


    progress.forEach(function (button) {

        button.addEventListener(
            'click',
            function () {

                const requested =
                    Number(
                        button.dataset
                            .registerStepButton
                    );

                if (requested <= currentStep) {
                    showStep(requested);
                }

            }
        );

    });


    /* PWD */

    const pwd =
        document.getElementById(
            'register_is_pwd'
        );

    const pwdDetails =
        document.getElementById(
            'registerPwdDetails'
        );

    const other =
        document.querySelector(
            'input[name="disability_types[]"][value="other"]'
        );

    const otherWrap =
        document.getElementById(
            'registerPwdOther'
        );


    function syncPwd() {

        const enabled =
            !!pwd?.checked;

        if (pwdDetails) {
            pwdDetails.hidden =
                !enabled;
        }

        syncOther();

    }


    function syncOther() {

        const show =
            !!pwd?.checked &&
            !!other?.checked;

        if (otherWrap) {
            otherWrap.hidden = !show;
        }

    }


    pwd?.addEventListener(
        'change',
        syncPwd
    );

    other?.addEventListener(
        'change',
        syncOther
    );


    syncPwd();
    showStep(1);

});
</script>

@endpush