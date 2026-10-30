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

@if(\Illuminate\Support\Facades\Route::has('it-support.tickets.index') && auth()->user()?->can('assign', new \App\Models\ItSupportTicket()))
    @include('support.tickets._dashboard')
@endif

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

@php
    $showStats = ($abilities['view_reports'] ?? false) || ! $isInstructor;
    $visibleQuickLinks = collect($quickLinks ?? [])
        ->filter(fn ($link) => \Illuminate\Support\Facades\Route::has($link['route']));

    // One tabbed panel instead of four stacked ones keeps the dashboard short.
    $groupTabs = ['people' => 'People & Learning', 'programmes' => 'Programmes & Outcomes', 'operations' => 'Operations'];
    $groupIcons = ['people' => 'fa-users', 'programmes' => 'fa-diagram-project', 'operations' => 'fa-building'];

    $dashboardTabs = [];
    if ($showStats) {
        foreach ($groupTabs as $tabKey => $group) {
            $dashboardTabs[$tabKey] = ['label' => $group, 'icon' => $groupIcons[$tabKey]];
        }
    }
    if ($visibleQuickLinks->isNotEmpty()) {
        $dashboardTabs['quick'] = ['label' => 'Quick Access', 'icon' => 'fa-bolt'];
    }
@endphp

@if($dashboardTabs)
    <section class="admin-panel executive-section">
        <x-form-tabs id="dashboard" label="Dashboard sections" :tabs="$dashboardTabs">
            @if($showStats)
                @foreach($groupTabs as $tabKey => $group)
                    <x-form-tab :name="$tabKey">
                        <div class="admin-stats-grid compact">
                            @foreach($groups[$group] as [$key, $label, $icon])
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
                    </x-form-tab>
                @endforeach
            @endif

            @if($visibleQuickLinks->isNotEmpty())
                <x-form-tab name="quick">
                    <p style="margin:0 0 12px;color:var(--admin-muted)">Open the administration areas available to your account.</p>
                    <div class="executive-links">
                        @foreach($visibleQuickLinks as $link)
                            <a href="{{ route($link['route']) }}">
                                <i class="fas {{ $link['icon'] }}"></i>
                                <span>{{ $link['label'] }}</span>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        @endforeach
                    </div>
                </x-form-tab>
            @endif
        </x-form-tabs>
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
