<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="@yield(
            'meta_description',
            'ElevateHer360 - Learning, mentorship, career development, jobs and digital resources.'
        )"
    >

    <title>
        @yield('title', 'ElevateHer360')
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    @stack('head')
</head>

<body>

{{-- Accessibility --}}
<a
    href="#main-content"
    class="skip-link"
>
    Skip to main content
</a>


{{-- =========================================================
     Public Header
     ========================================================= --}}
<header class="site-header">

    <div class="nav">

        {{-- Brand --}}
        <a
            href="{{ route('home') }}"
            class="brand"
            aria-label="ElevateHer360 home"
        >

            <span class="brand-mark">
                E360
            </span>

            <span>
                ElevateHer360

                <small>
                    Women in Technology Uganda
                </small>
            </span>

        </a>


        {{-- Primary Navigation --}}
        <nav
            class="nav-links"
            aria-label="Main navigation"
        >

            <a
                href="{{ route('home') }}"
                class="{{ request()->routeIs('home') ? 'active' : '' }}"
            >
                Home
            </a>

            <a
                href="{{ route('learning.index') }}"
                class="{{ request()->routeIs('learning.*') ? 'active' : '' }}"
            >
                Learning
            </a>

            <a
                href="{{ route('jobs.index') }}"
                class="{{ request()->routeIs('jobs.*') ? 'active' : '' }}"
            >
                Jobs
            </a>

            <a
                href="{{ route('library.index') }}"
                class="{{ request()->routeIs('library.*') ? 'active' : '' }}"
            >
                Library
            </a>


            @auth

                @if(! auth()->user()->isStaff())

                    <a
                        href="{{ route('mentorship.dashboard') }}"
                        class="{{ request()->routeIs('mentorship.*') ? 'active' : '' }}"
                    >
                        Mentorship
                    </a>

                @endif

            @endauth

        </nav>


        {{-- Account Actions --}}
        <div class="nav-actions">

            @guest

                <a
                    href="{{ route('login') }}"
                    class="btn btn-outline btn-sm"
                >
                    <i class="fas fa-right-to-bracket"></i>

                    <span>
                        Sign In
                    </span>
                </a>

                <a
                    href="{{ route('register') }}"
                    class="btn btn-primary btn-sm"
                >
                    <i class="fas fa-user-plus"></i>

                    <span>
                        Register
                    </span>
                </a>

            @else

                @if(auth()->user()->isStaff())

                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="btn btn-primary btn-sm"
                    >
                        <i class="fas fa-gauge-high"></i>

                        <span>
                            Dashboard
                        </span>
                    </a>

                @else

                    <a
                        href="{{ route('dashboard') }}"
                        class="btn btn-primary btn-sm"
                    >
                        <i class="fas fa-gauge-high"></i>

                        <span>
                            Dashboard
                        </span>
                    </a>

                @endif

            @endguest

        </div>

    </div>

</header>


{{-- =========================================================
     Main Content

     Homepage:
     - No fixed page-shell
     - Sections can span full browser width

     Other pages:
     - Constrained to normal page width
     ========================================================= --}}
<main
    id="main-content"
    class="{{ request()->routeIs('home') ? 'site-main site-main-home' : 'site-main page-shell' }}"
>

    {{-- Flash Messages --}}
    @if(
        session('success')
        || session('error')
        || session('warning')
        || session('info')
        || $errors->any()
    )

        <div class="{{ request()->routeIs('home') ? 'container global-messages' : 'global-messages' }}">

            @if(session('success'))

                <div class="success-box">

                    <i class="fas fa-circle-check"></i>

                    <span>
                        {{ session('success') }}
                    </span>

                </div>

            @endif


            @if(session('error'))

                <div class="error-box">

                    <i class="fas fa-circle-exclamation"></i>

                    <span>
                        {{ session('error') }}
                    </span>

                </div>

            @endif


            @if(session('warning'))

                <div class="warning-box">

                    <i class="fas fa-triangle-exclamation"></i>

                    <span>
                        {{ session('warning') }}
                    </span>

                </div>

            @endif


            @if(session('info'))

                <div class="info-box">

                    <i class="fas fa-circle-info"></i>

                    <span>
                        {{ session('info') }}
                    </span>

                </div>

            @endif


            @if($errors->any())

                <div class="error-box">

                    <strong>
                        <i class="fas fa-circle-exclamation"></i>

                        Please correct the following:
                    </strong>

                    <ul>

                        @foreach($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif

        </div>

    @endif


    @yield('content')

</main>


{{-- =========================================================
     Footer
     ========================================================= --}}
<footer class="site-footer">

    <div class="container footer">

        <div>

            <strong class="footer-brand">
                ElevateHer360
            </strong>

           <div class="footer-copy">
    <i class="fas fa-copyright"></i>
    {{ date('Y') }}
    Women in Technology Uganda
</div>

        </div>


        <nav
            class="footer-links"
            aria-label="Footer navigation"
        >

            <a href="{{ route('home') }}">
                Home
            </a>

            <a href="{{ route('learning.index') }}">
                Learning
            </a>

            <a href="{{ route('jobs.index') }}">
                Jobs
            </a>

            <a href="{{ route('library.index') }}">
                Library
            </a>

            @guest

                <a href="{{ route('login') }}">
                    Sign In
                </a>

                <a href="{{ route('register') }}">
                    Register
                </a>

            @endguest

        </nav>

    </div>

</footer>


{{-- =========================================================
     Password Visibility
     ========================================================= --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {

        document
            .querySelectorAll('[data-password-toggle]')
            .forEach(function (button) {

                button.addEventListener('click', function () {

                    const fieldId =
                        button.getAttribute('data-password-toggle');

                    const input =
                        document.getElementById(fieldId);

                    if (!input) {
                        return;
                    }

                    const passwordVisible =
                        input.type === 'text';

                    input.type =
                        passwordVisible
                            ? 'password'
                            : 'text';

                    button.innerHTML =
                        passwordVisible
                            ? '<i class="fas fa-eye"></i>'
                            : '<i class="fas fa-eye-slash"></i>';

                    button.setAttribute(
                        'aria-label',
                        passwordVisible
                            ? 'Show password'
                            : 'Hide password'
                    );

                });

            });

    });
</script>


@stack('scripts')

</body>
</html>