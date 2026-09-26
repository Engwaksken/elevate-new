@php
    $topbarSearchRoute = null;

    foreach ([
        'admin.search',
        'admin.global-search',
        'admin.search.index',
    ] as $candidate) {
        if (Route::has($candidate)) {
            $topbarSearchRoute = route($candidate);
            break;
        }
    }

    $topbarProfileRoute = Route::has('admin.profile.edit')
        ? route('admin.profile.edit')
        : (Route::has('admin.profile') ? route('admin.profile') : null);

    $topbarPasswordRoute = $topbarProfileRoute
        ? $topbarProfileRoute . '#password'
        : null;

    $topbarLogoutRoute = null;

    foreach (['logout', 'admin.logout'] as $candidate) {
        if (Route::has($candidate)) {
            $topbarLogoutRoute = route($candidate);
            break;
        }
    }

    $topbarUser = auth()->user();

    $topbarDisplayName = $topbarUser?->name
        ?? $topbarUser?->full_name
        ?? $topbarUser?->email
        ?? 'Account';

    $topbarInitial = mb_strtoupper(mb_substr(trim($topbarDisplayName), 0, 1));

    $topbarRole = null;

    if ($topbarUser && method_exists($topbarUser, 'roles')) {
        try {
            $topbarRole = optional($topbarUser->roles()->first())->name;
        } catch (\Throwable $e) {
            $topbarRole = null;
        }
    }
@endphp

<header class="eh-admin-topbar"
        data-admin-topbar="true"
        data-auth-name="{{ $topbarDisplayName }}"
        data-auth-email="{{ $topbarUser?->email }}">

    <div class="eh-admin-topbar__left">
        <button type="button"
                class="eh-admin-topbar__mobile-menu"
                data-admin-sidebar-toggle
                aria-label="Toggle navigation">
            <i class="fas fa-bars"></i>
        </button>

        <div class="eh-admin-topbar__context">
            <span class="eh-admin-topbar__eyebrow">ElevateHer360</span>
            <strong>{{ $topbarRole ?: 'Administration' }}</strong>
        </div>
    </div>

    <div class="eh-admin-topbar__centre">
        @if($topbarSearchRoute)
            <form method="GET"
                  action="{{ $topbarSearchRoute }}"
                  class="eh-admin-topbar__search"
                  role="search"
                  novalidate>

                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>

                <input type="search"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="Search users, courses, events, jobs, workplans..."
                       aria-label="Global search"
                       data-no-hint="true"
                       data-form-help="off">

                <button type="submit" aria-label="Search">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        @endif
    </div>

    <div class="eh-admin-topbar__right">
        @if(Route::has('admin.notifications.index'))
            <a href="{{ route('admin.notifications.index') }}"
               class="eh-admin-topbar__icon-btn"
               title="Notifications">
                <i class="fas fa-bell"></i>
            </a>
        @elseif(Route::has('notifications.index'))
            <a href="{{ route('notifications.index') }}"
               class="eh-admin-topbar__icon-btn"
               title="Notifications">
                <i class="fas fa-bell"></i>
            </a>
        @endif

        <div class="eh-admin-profile" data-profile-menu>
            <button type="button"
                    class="eh-admin-profile__trigger"
                    data-profile-menu-trigger
                    aria-expanded="false">

                <span class="eh-admin-profile__avatar">{{ $topbarInitial }}</span>

                <span class="eh-admin-profile__meta">
                    <strong>{{ $topbarDisplayName }}</strong>
                    <small>{{ $topbarRole ?: ($topbarUser?->email ?? 'Signed in') }}</small>
                </span>

                <i class="fas fa-chevron-down"></i>
            </button>

            <div class="eh-admin-profile__menu"
                 data-profile-menu-panel
                 hidden>

                <div class="eh-admin-profile__summary">
                    <span class="eh-admin-profile__avatar eh-admin-profile__avatar--large">
                        {{ $topbarInitial }}
                    </span>

                    <div>
                        <strong>{{ $topbarDisplayName }}</strong>
                        <small>{{ $topbarUser?->email }}</small>
                    </div>
                </div>

                @if($topbarProfileRoute)
                    <a href="{{ $topbarProfileRoute }}">
                        <i class="fas fa-user"></i>
                        <span>My Profile</span>
                    </a>

                    <a href="{{ $topbarPasswordRoute }}">
                        <i class="fas fa-key"></i>
                        <span>Change Password</span>
                    </a>
                @endif

                @if(Route::has('admin.settings.index'))
                    <a href="{{ route('admin.settings.index') }}">
                        <i class="fas fa-gears"></i>
                        <span>Account Settings</span>
                    </a>
                @endif

                @if($topbarLogoutRoute)
                    <div class="eh-admin-profile__divider"></div>

                    <form method="POST"
                          action="{{ $topbarLogoutRoute }}"
                          class="eh-admin-profile__logout">
                        @csrf

                        <button type="submit">
                            <i class="fas fa-right-from-bracket"></i>
                            <span>Sign Out</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if($topbarLogoutRoute)
            <form method="POST"
                  action="{{ $topbarLogoutRoute }}"
                  class="eh-admin-topbar__logout">
                @csrf

                <button type="submit"
                        class="eh-admin-topbar__logout-btn">
                    <i class="fas fa-right-from-bracket"></i>
                    <span>Sign Out</span>
                </button>
            </form>
        @endif
    </div>
</header>
