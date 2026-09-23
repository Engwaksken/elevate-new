@extends('layouts.app')

@section(
    'title',
    'Staff Login | ElevateHer360'
)

@section('content')

<div class="auth-layout auth-layout-staff">


    {{-- ===================================================
         LEFT BRAND PANEL
         =================================================== --}}

    <section class="auth-side">

        <div class="auth-side-content">

            <div class="gold-line"></div>


            <span class="staff-badge">
                <i class="fas fa-shield-halved"></i>
                WITU STAFF PORTAL
            </span>


            <h1>
                Manage programmes and participant impact.
            </h1>


            <p>
                Secure access for authorised WITU staff,
                programme teams and administrators.
            </p>


            <div class="staff-auth-features">

                <div class="staff-auth-feature">

                    <span>
                        <i class="fas fa-lock"></i>
                    </span>

                    <div>
                        <strong>
                            Secure access
                        </strong>

                        <small>
                            Staff-only access to authorised
                            management tools.
                        </small>
                    </div>

                </div>


                <div class="staff-auth-feature">

                    <span>
                        <i class="fas fa-chart-line"></i>
                    </span>

                    <div>
                        <strong>
                            Programme management
                        </strong>

                        <small>
                            Manage participants, programmes,
                            learning and impact data.
                        </small>
                    </div>

                </div>


                <div class="staff-auth-feature">

                    <span>
                        <i class="fas fa-users-gear"></i>
                    </span>

                    <div>
                        <strong>
                            Role-based access
                        </strong>

                        <small>
                            Access is controlled by your
                            assigned staff role.
                        </small>
                    </div>

                </div>

            </div>

        </div>

    </section>



    {{-- ===================================================
         STAFF LOGIN PANEL
         =================================================== --}}

    <section class="auth-panel">

        <div class="auth-panel-inner">


            <span class="staff-badge staff-badge-light">
                <i class="fas fa-user-shield"></i>
                Authorised staff only
            </span>


            <div class="auth-heading">

                <h2>
                    Staff Sign In
                </h2>

                <p class="subtitle">
                    Enter your authorised WITU staff
                    credentials.
                </p>

            </div>



            {{-- ===========================================
                 INLINE FORM FEEDBACK
                 =========================================== --}}

            <div class="form-feedback">


                @if(session('success'))

                    <div
                        class="
                            flash-message
                            form-alert
                            form-alert-success
                        "
                        data-auto-dismiss
                        role="status"
                    >

                        <span class="form-alert-icon">
                            <i class="fas fa-circle-check"></i>
                        </span>

                        <div class="form-alert-content">
                            {{ session('success') }}
                        </div>

                    </div>

                @endif



                @if(session('error'))

                    <div
                        class="
                            flash-message
                            form-alert
                            form-alert-error
                        "
                        data-auto-dismiss
                        role="alert"
                    >

                        <span class="form-alert-icon">
                            <i class="fas fa-circle-exclamation"></i>
                        </span>

                        <div class="form-alert-content">
                            {{ session('error') }}
                        </div>

                    </div>

                @endif



                @if(session('warning'))

                    <div
                        class="
                            flash-message
                            form-alert
                            form-alert-warning
                        "
                        data-auto-dismiss
                        role="alert"
                    >

                        <span class="form-alert-icon">
                            <i class="fas fa-triangle-exclamation"></i>
                        </span>

                        <div class="form-alert-content">
                            {{ session('warning') }}
                        </div>

                    </div>

                @endif



                @if(session('info'))

                    <div
                        class="
                            flash-message
                            form-alert
                            form-alert-info
                        "
                        data-auto-dismiss
                        role="status"
                    >

                        <span class="form-alert-icon">
                            <i class="fas fa-circle-info"></i>
                        </span>

                        <div class="form-alert-content">
                            {{ session('info') }}
                        </div>

                    </div>

                @endif



                @if($errors->any())

                    <div
                        class="
                            flash-message
                            form-alert
                            form-alert-error
                        "
                        data-auto-dismiss
                        role="alert"
                    >

                        <span class="form-alert-icon">
                            <i class="fas fa-circle-exclamation"></i>
                        </span>


                        <div class="form-alert-content">

                            @if($errors->count() === 1)

                                {{ $errors->first() }}

                            @else

                                <strong>
                                    Please correct the following:
                                </strong>

                                <ul>
                                    @foreach($errors->all() as $error)

                                        <li>
                                            {{ $error }}
                                        </li>

                                    @endforeach
                                </ul>

                            @endif

                        </div>

                    </div>

                @endif


            </div>



            {{-- ===========================================
                 LOGIN FORM
                 =========================================== --}}

            <form
                method="POST"
                action="{{ route('admin.login.attempt') }}"
                class="auth-form"
            >

                @csrf



                {{-- Work Email --}}

                <div class="form-group">

                    <label for="staff_email">

                        Work email

                        <span class="required">
                            *
                        </span>

                    </label>


                    <div class="input-with-icon">

                        <span class="input-leading-icon">
                            <i class="fas fa-envelope"></i>
                        </span>


                        <input
                            id="staff_email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="e.g. name@witu.org"
                            autocomplete="email"
                            required
                            autofocus
                            class="{{
                                $errors->has('email')
                                    ? 'is-invalid'
                                    : ''
                            }}"
                        >

                    </div>


                    <small class="form-hint">
                        Use the work email linked to your
                        WITU staff account.
                    </small>


                    @error('email')

                        <small class="field-error">
                            <i class="fas fa-circle-exclamation"></i>
                            {{ $message }}
                        </small>

                    @enderror

                </div>



                {{-- Password --}}

                <div class="form-group">

                    <label for="staff_password">

                        Password

                        <span class="required">
                            *
                        </span>

                    </label>


                    <div class="password-wrap input-with-icon">

                        <span class="input-leading-icon">
                            <i class="fas fa-lock"></i>
                        </span>


                        <input
                            id="staff_password"
                            type="password"
                            name="password"
                            placeholder="Enter your staff password"
                            autocomplete="current-password"
                            required
                            class="{{
                                $errors->has('password')
                                    ? 'is-invalid'
                                    : ''
                            }}"
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


                    <small class="form-hint">
                        Enter the password for your
                        authorised staff account.
                    </small>


                    @error('password')

                        <small class="field-error">
                            <i class="fas fa-circle-exclamation"></i>
                            {{ $message }}
                        </small>

                    @enderror

                </div>



                {{-- Remember Me --}}

                <label
                    for="staff_remember"
                    class="remember-option"
                >

                    <input
                        id="staff_remember"
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
                            Keep this staff account signed in
                            on this device.
                        </small>

                    </span>

                </label>



                {{-- Submit --}}

                <button
                    type="submit"
                    class="btn btn-primary btn-block auth-submit"
                >

                    <i class="fas fa-shield-halved"></i>

                    Sign In

                </button>


                <div class="staff-security-note">

                    <i class="fas fa-lock"></i>

                    <span>
                        This sign-in page is restricted to
                        authorised WITU staff accounts.
                    </span>

                </div>

            </form>

        </div>

    </section>

</div>

@endsection