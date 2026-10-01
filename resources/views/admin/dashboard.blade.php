@extends('layouts.admin')

@section('title', 'Dashboard | ElevateHer360 Administration')

@section('content')
@php
    $displayName = $user->name
        ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
        ?: 'Staff Member';

    $isInstructor = method_exists($user, 'hasAnyRole')
        && ! $user->isSuperAdmin()
        && $user->hasAnyRole(['instructor', 'trainer']);

    $groups = [
        'People & Learning' => [
            ['participants', 'Participants', 'fa-users'],
            ['staff', 'Staff', 'fa-user-tie'],
            ['published_courses', 'Published Courses', 'fa-graduation-cap'],
            ['active_enrolments', 'Active Enrolments', 'fa-user-check'],
            ['completed_learners', 'Completed Learners', 'fa-circle-check'],
            ['certificates', 'Certificates', 'fa-award'],
        ],
        'Programmes & Outcomes' => [
            ['active_programmes', 'Active Programmes', 'fa-diagram-project'],
            ['projects', 'Projects', 'fa-folder-tree'],
            ['cohorts', 'Cohorts', 'fa-people-group'],
            ['active_mentorships', 'Active Mentorships', 'fa-handshake'],
            ['job_applications', 'Job Applications', 'fa-briefcase'],
            ['verified_outcomes', 'Verified Outcomes', 'fa-bullseye'],
            ['approved_workplans', 'Approved Workplans', 'fa-list-check'],
            ['pending_indicator_results', 'Pending Indicator Results', 'fa-clock'],
        ],
        'Operations' => [
            ['active_employees', 'Active Employees', 'fa-id-badge'],
            ['pending_leave', 'Pending Leave', 'fa-calendar-minus'],
            ['active_assets', 'Active Assets', 'fa-laptop'],
            ['open_purchase_requests', 'Open Purchase Requests', 'fa-cart-shopping'],
            ['branches', 'Branches', 'fa-building'],
            ['events', 'Events', 'fa-calendar-days'],
        ],
    ];
@endphp

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">
            {{ $isInstructor ? 'Instructor Workspace' : 'Administration Overview' }}
        </span>

        <h1>Dashboard</h1>

        <p>
            Welcome, {{ $displayName }}.
            @if($isInstructor)
                Access your assigned courses, learning delivery tools and relevant platform updates.
            @else
                View ElevateHer360 operations, programme delivery and organisation-wide performance from one dashboard.
            @endif
        </p>
    </div>
</div>

@if($isInstructor)
    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <h2>Instructor Workspace</h2>
                <p>Your course management functions are available through My Courses.</p>
            </div>
        </div>

        <div class="executive-links">
            @if(\Illuminate\Support\Facades\Route::has('admin.my-courses'))
                <a href="{{ route('admin.my-courses') }}">
                    <i class="fas fa-chalkboard-user"></i>
                    <span>My Courses</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('calendar.index'))
                <a href="{{ route('calendar.index') }}">
                    <i class="fas fa-calendar-days"></i>
                    <span>Calendar</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.profile.edit'))
                <a href="{{ route('admin.profile.edit') }}">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
            @endif
        </div>
    </section>
@endif

@if(($abilities['view_reports'] ?? false) || ! $isInstructor)
    @foreach($groups as $group => $items)
        <section class="admin-panel executive-section">
            <div class="admin-panel-head">
                <div>
                    <h2>{{ $group }}</h2>
                </div>
            </div>

            <div class="admin-stats-grid compact">
                @foreach($items as [$key, $label, $icon])
                    <div class="admin-stat">
                        <span class="admin-stat-icon">
                            <i class="fas {{ $icon }}"></i>
                        </span>

                        <div>
                            <small>{{ $label }}</small>
                            <strong>{{ number_format((float) ($stats[$key] ?? 0)) }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
@endif

@if(!empty($quickLinks))
    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <h2>Quick Management Access</h2>
                <p>Open the administration areas available to your account.</p>
            </div>
        </div>

        <div class="executive-links">
            @foreach($quickLinks as $link)
                @if(\Illuminate\Support\Facades\Route::has($link['route']))
                    <a href="{{ route($link['route']) }}">
                        <i class="fas {{ $link['icon'] }}"></i>
                        <span>{{ $link['label'] }}</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                @endif
            @endforeach
        </div>
    </section>
@endif

@if(
    \Illuminate\Support\Facades\Route::has('admin.notifications.index')
    && ($stats['notifications'] ?? 0) > 0
)
    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <h2>Notifications</h2>
                <p>{{ number_format((int) $stats['notifications']) }} notification records are currently available.</p>
            </div>

            <a class="btn" href="{{ route('admin.notifications.index') }}">
                View Notifications
            </a>
        </div>
    </section>
@endif
@endsection
