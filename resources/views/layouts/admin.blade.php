<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'ElevateHer360 Administration')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
@include('partials.dynamic-branding')
</head>
<body class="admin-app-body eh-admin-canonical-layout">

    @include('partials.admin-sidebar')

    <div class="eh-admin-shell" data-eh-admin-shell>
        <div class="admin-overlay" data-admin-overlay></div>

        @include('partials.admin-topbar')

        <header class="admin-mobile-header">
            <button type="button"
                    data-admin-sidebar-toggle
                    aria-label="Open navigation">
                <i class="fas fa-bars"></i>
            </button>

            <a href="{{ route('admin.dashboard') }}">
                <span>E360</span>
                <strong>Administration</strong>
            </a>

            <i class="fas fa-user-shield"></i>
        </header>

        <main class="eh-admin-content" data-eh-admin-content>
            @if(session('success'))
                <div class="flash-message form-alert form-alert-success" data-auto-dismiss>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="flash-message form-alert form-alert-error" data-auto-dismiss>
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="flash-message form-alert form-alert-error" data-auto-dismiss>
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.body;

            document.querySelectorAll('[data-admin-sidebar-toggle]').forEach((button) => {
                button.addEventListener('click', () => {
                    body.classList.toggle('admin-sidebar-open');
                });
            });

            document.querySelector('[data-admin-overlay]')?.addEventListener('click', () => {
                body.classList.remove('admin-sidebar-open');
            });

            document.querySelectorAll('[data-auto-dismiss]').forEach((message) => {
                setTimeout(() => {
                    message.classList.add('is-fading');
                    setTimeout(() => message.remove(), 450);
                }, 5000);
            });
        });
    </script>

    @include('partials.global-assistive-tools')
    @stack('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const seen = new Set();

    document.querySelectorAll(
        '.flash-message, .form-alert, .success-box, .error-box, .warning-box, .info-box'
    ).forEach((message) => {
        const text = (message.textContent || '')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();

        if (!text) return;

        if (seen.has(text)) {
            message.classList.add('eh-duplicate-flash');
            message.remove();
            return;
        }

        seen.add(text);
    });
});
</script>
</body>
</html>
