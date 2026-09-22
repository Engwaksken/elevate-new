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

<a
    href="#main-content"
    class="skip-link"
>
    Skip to main content
</a>


<header class="site-header">

    <div class="nav">

        <a
            href="{{ route('home') }}"
            class="brand"
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


        <nav class="nav-links">

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

            @auth

                @if(!auth()->user()->isStaff())

                    <a href="{{ route('mentorship.dashboard') }}">
                        Mentorship
                    </a>

                @endif

            @endauth

        </nav>


        <div class="nav-actions">

            @guest

                <a
                    href="{{ route('login') }}"
                    class="btn btn-outline btn-sm"
                >
                    <i class="fas fa-right-to-bracket"></i>

                    Sign In
                </a>

                <a
                    href="{{ route('register') }}"
                    class="btn btn-primary btn-sm"
                >
                    <i class="fas fa-user-plus"></i>

                    Register
                </a>

            @else

                @if(auth()->user()->isStaff())

                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="btn btn-primary btn-sm"
                    >
                        <i class="fas fa-gauge-high"></i>

                        Dashboard
                    </a>

                @else

                    <a
                        href="{{ route('dashboard') }}"
                        class="btn btn-primary btn-sm"
                    >
                        <i class="fas fa-gauge-high"></i>

                        Dashboard
                    </a>

                @endif

            @endguest

        </div>

    </div>

</header>


<main
    id="main-content"
    class="page-shell"
>

    @if(session('success'))

        <div class="success-box">

            <i class="fas fa-circle-check"></i>

            {{ session('success') }}

        </div>

    @endif


    @if(session('error'))

        <div class="error-box">

            <i class="fas fa-circle-exclamation"></i>

            {{ session('error') }}

        </div>

    @endif


    @if(session('warning'))

        <div class="warning-box">

            <i class="fas fa-triangle-exclamation"></i>

            {{ session('warning') }}

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


    @yield('content')

</main>


<footer class="site-footer">

    <div class="container footer">

        <span>
            © {{ date('Y') }}
            ElevateHer360 · Women in Technology Uganda
        </span>

        <div class="d-flex gap-2 flex-wrap">

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

        </div>

    </div>

</footer>


<script>
    document.addEventListener('DOMContentLoaded', function () {

        document
            .querySelectorAll('[data-password-toggle]')
            .forEach(function (button) {

                button.addEventListener('click', function () {

                    const fieldId =
                        button.getAttribute(
                            'data-password-toggle'
                        );

                    const input =
                        document.getElementById(fieldId);

                    if (!input) {
                        return;
                    }

                    const showing =
                        input.type === 'text';

                    input.type =
                        showing
                            ? 'password'
                            : 'text';

                    button.innerHTML =
                        showing
                            ? '<i class="fas fa-eye"></i>'
                            : '<i class="fas fa-eye-slash"></i>';
                });

            });

    });
</script>

@stack('scripts')

</body>
</html>