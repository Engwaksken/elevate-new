<aside id="participantSidebar" class="participant-sidebar" aria-label="Participant navigation">
    <div class="ps-brand">
        <a href="{{ route('dashboard') }}" class="ps-brand-link">
            <span class="ps-mark">E360</span>
            <span><strong>ElevateHer360</strong><small>Women in Technology Uganda</small></span>
        </a>
        <button type="button" class="ps-close" data-sidebar-close aria-label="Close navigation"><i class="fas fa-xmark"></i></button>
    </div>

    <div class="ps-user">
        <span class="ps-avatar">{{ strtoupper(substr(auth()->user()->name ?? auth()->user()->email ?? 'U', 0, 1)) }}</span>
        <span class="ps-user-copy"><strong>{{ auth()->user()->name ?? 'Participant' }}</strong><small>{{ auth()->user()->email }}</small></span>
    </div>

    <nav class="ps-nav">
        <span class="ps-label">Overview</span>
        <a href="{{ route('dashboard') }}" class="ps-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="fas fa-gauge-high"></i><span>Dashboard</span></a>
        @if(Route::has('calendar.index'))<a href="{{ route('calendar.index') }}" class="ps-link {{ request()->routeIs('calendar.*') ? 'active' : '' }}"><i class="fas fa-calendar-days"></i><span>Calendar</span></a>@endif
        @if(Route::has('notifications.index'))<a href="{{ route('notifications.index') }}" class="ps-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}"><i class="fas fa-bell"></i><span>Notifications</span></a>@endif

        <span class="ps-label">Growth</span>
        @if(Route::has('learning.index'))<a href="{{ route('learning.index') }}" class="ps-link {{ request()->routeIs('learning.*') ? 'active' : '' }}"><i class="fas fa-graduation-cap"></i><span>Learning</span></a>@endif
        @if(Route::has('mentorship.dashboard'))<a href="{{ route('mentorship.dashboard') }}" class="ps-link {{ request()->routeIs('mentorship.*') ? 'active' : '' }}"><i class="fas fa-user-group"></i><span>Mentorship</span></a>@endif
        @if(Route::has('career.resume.index'))<a href="{{ route('career.resume.index') }}" class="ps-link {{ request()->routeIs('career.*') ? 'active' : '' }}"><i class="fas fa-file-lines"></i><span>Resume Builder</span></a>@endif

        <span class="ps-label">Opportunities</span>
        @if(Route::has('jobs.index'))<a href="{{ route('jobs.index') }}" class="ps-link {{ request()->routeIs('jobs.index','jobs.show') ? 'active' : '' }}"><i class="fas fa-briefcase"></i><span>Jobs</span></a>@endif
        @if(Route::has('jobs.applications'))<a href="{{ route('jobs.applications') }}" class="ps-link {{ request()->routeIs('jobs.applications') ? 'active' : '' }}"><i class="fas fa-folder-open"></i><span>My Applications</span></a>@endif
        @if(Route::has('library.index'))<a href="{{ route('library.index') }}" class="ps-link {{ request()->routeIs('library.*') ? 'active' : '' }}"><i class="fas fa-book-open"></i><span>Library</span></a>@endif

        <span class="ps-label">Account</span>
        @if(Route::has('profile.edit'))<a href="{{ route('profile.edit') }}" class="ps-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"><i class="fas fa-user"></i><span>Profile</span></a>@endif
    </nav>

    <div class="ps-footer">
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button type="submit" class="ps-logout"><i class="fas fa-right-from-bracket"></i><span>Sign Out</span></button>
        </form>
    </div>
</aside>
