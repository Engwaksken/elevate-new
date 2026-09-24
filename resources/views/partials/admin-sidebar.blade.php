<aside id="adminSidebar" class="admin-sidebar" aria-label="Administration navigation">
<div class="admin-sidebar-brand">
<a href="{{ route('admin.dashboard') }}"><span class="admin-brand-mark">E360</span><span><strong>ElevateHer360</strong><small>Administration</small></span></a>
<button type="button" class="admin-sidebar-close" data-admin-sidebar-toggle aria-label="Close navigation"><i class="fas fa-xmark"></i></button>
</div>

<div class="admin-user-card">
<span class="admin-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A',0,1)) }}</span>
<span class="admin-user-copy"><strong>{{ auth()->user()->name ?? 'Administrator' }}</strong><small>{{ auth()->user()->email ?? '' }}</small><em>{{ auth()->user()->roles->pluck('name')->first() ?? 'Staff' }}</em></span>
</div>

<nav class="admin-nav">
<div class="admin-nav-section"><span>Overview</span>
<a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard')?'active':'' }}"><i class="fas fa-gauge-high"></i><b>Dashboard</b></a>
</div>

<div class="admin-nav-section"><span>People & Access</span>
@if(Route::has('admin.users.index'))<a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*')?'active':'' }}"><i class="fas fa-users"></i><b>Users</b></a>@endif
@if(Route::has('admin.roles.index'))<a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*')?'active':'' }}"><i class="fas fa-user-lock"></i><b>Roles & Permissions</b></a>@endif
</div>

<div class="admin-nav-section"><span>Programme Management</span>
@if(Route::has('admin.programmes.index'))<a href="{{ route('admin.programmes.index') }}" class="{{ request()->routeIs('admin.programmes.*')?'active':'' }}"><i class="fas fa-diagram-project"></i><b>Programmes</b></a>@endif
@if(Route::has('admin.projects.index'))<a href="{{ route('admin.projects.index') }}" class="{{ request()->routeIs('admin.projects.*')?'active':'' }}"><i class="fas fa-folder-tree"></i><b>Projects</b></a>@endif
@if(Route::has('admin.cohorts.index'))<a href="{{ route('admin.cohorts.index') }}" class="{{ request()->routeIs('admin.cohorts.*')?'active':'' }}"><i class="fas fa-people-group"></i><b>Cohorts</b></a>@endif
@if(Route::has('admin.branches.index'))<a href="{{ route('admin.branches.index') }}" class="{{ request()->routeIs('admin.branches.*')?'active':'' }}"><i class="fas fa-building"></i><b>Branches</b></a>@endif
</div>

<div class="admin-nav-section"><span>Programme Delivery</span>
@if(Route::has('admin.elearning.courses.index'))<a href="{{ route('admin.elearning.courses.index') }}" class="{{ request()->routeIs('admin.elearning.*')?'active':'' }}"><i class="fas fa-graduation-cap"></i><b>Learning</b></a>@endif
@if(Route::has('admin.mentorship.index'))<a href="{{ route('admin.mentorship.index') }}" class="{{ request()->routeIs('admin.mentorship.*')?'active':'' }}"><i class="fas fa-handshake"></i><b>Mentorship</b></a>@endif
@if(Route::has('admin.jobs.index'))<a href="{{ route('admin.jobs.index') }}" class="{{ request()->routeIs('admin.jobs.*')?'active':'' }}"><i class="fas fa-briefcase"></i><b>Jobs</b></a>@endif
@if(Route::has('admin.library.index'))<a href="{{ route('admin.library.index') }}" class="{{ request()->routeIs('admin.library.*')?'active':'' }}"><i class="fas fa-book-open"></i><b>Library</b></a>@endif
</div>

<div class="admin-nav-section"><span>Planning & MEAL</span>
@if(Route::has('admin.workplans.index'))<a href="{{ route('admin.workplans.index') }}" class="{{ request()->routeIs('admin.workplans.*')?'active':'' }}"><i class="fas fa-list-check"></i><b>Workplans</b></a>@endif
@if(Route::has('admin.indicators.index'))<a href="{{ route('admin.indicators.index') }}" class="{{ request()->routeIs('admin.indicators.*')?'active':'' }}"><i class="fas fa-chart-column"></i><b>Indicators & MEAL</b></a>@endif
</div>

<div class="admin-nav-section"><span>Operations</span>
@if(Route::has('admin.hr.index'))<a href="{{ route('admin.hr.index') }}" class="{{ request()->routeIs('admin.hr.*')?'active':'' }}"><i class="fas fa-id-badge"></i><b>Human Resources</b></a>@endif
@if(Route::has('admin.assets.index'))<a href="{{ route('admin.assets.index') }}" class="{{ request()->routeIs('admin.assets.*')?'active':'' }}"><i class="fas fa-laptop"></i><b>Assets</b></a>@endif
@if(Route::has('admin.procurement.index'))<a href="{{ route('admin.procurement.index') }}" class="{{ request()->routeIs('admin.procurement.*')?'active':'' }}"><i class="fas fa-cart-shopping"></i><b>Procurement</b></a>@endif
@if(Route::has('admin.career-ai.index'))<a href="{{ route('admin.career-ai.index') }}" class="{{ request()->routeIs('admin.career-ai.*')?'active':'' }}"><i class="fas fa-wand-magic-sparkles"></i><b>Career AI</b></a>@endif
@if(Route::has('admin.migrations.index'))<a href="{{ route('admin.migrations.index') }}" class="{{ request()->routeIs('admin.migrations.*')?'active':'' }}"><i class="fas fa-database"></i><b>Migrations</b></a>@endif
</div>
</nav>

<div class="admin-sidebar-footer">
<form method="POST" action="{{ route('admin.logout') }}">@csrf
<button type="submit"><i class="fas fa-right-from-bracket"></i><span>Sign Out</span></button>
</form>
</div>
</aside>
