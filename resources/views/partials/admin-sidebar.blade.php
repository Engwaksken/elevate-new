@php
    $sidebarUser = auth()->user();
    $sidebarName = $sidebarUser?->name
        ?? trim(($sidebarUser?->first_name ?? '') . ' ' . ($sidebarUser?->last_name ?? ''))
        ?: 'Administrator';

    $sidebarEmail = $sidebarUser?->email ?? '';
    $sidebarInitial = strtoupper(mb_substr(trim($sidebarName), 0, 1));

    $routeExists = fn (string $name): bool => \Illuminate\Support\Facades\Route::has($name);

    $canAccess = function (?string $permission) use ($sidebarUser): bool {
        if (! $sidebarUser) {
            return false;
        }

        if (method_exists($sidebarUser, 'isSuperAdmin') && $sidebarUser->isSuperAdmin()) {
            return true;
        }

        if ($permission === null || $permission === '') {
            return true;
        }

        return method_exists($sidebarUser, 'hasPermission')
            && $sidebarUser->hasPermission($permission);
    };

    $routePermissions = [
        'admin.users.index' => 'users.view',
        'admin.roles.index' => 'roles.manage',

        'admin.programmes.index' => 'programmes.view',
        'admin.projects.index' => 'programmes.view',
        'admin.cohorts.index' => 'cohorts.view',
        'admin.branches.index' => 'programmes.view',

        'admin.elearning.courses.index' => 'courses.view',
        'admin.course-calls.index' => 'course_calls.view',
        'admin.elearning.assignments.index' => 'courses.view',
        'admin.elearning.enrolments.index' => 'students.view',
        'admin.elearning.learning-files.index' => 'courses.view',
        'admin.elearning.certificates.index' => 'courses.view',
        'admin.elearning.bulk-enrolment.create' => 'students.edit',

        'admin.mentorship.index' => 'mentors.view',
        'admin.jobs.index' => 'jobs.view',
        'admin.library.index' => 'library.manage',
        'admin.events.index' => 'calendar.manage',
        'admin.events.calendar' => 'calendar.manage',

        'admin.workplans.index' => 'workplans.view',
        'admin.tasks.index' => 'tasks.manage',
        'admin.deliverables.index' => 'tasks.manage',
        'admin.indicators.index' => 'indicators.view',
        'admin.results-framework.index' => 'meal.view',
        'admin.meal.dashboard' => 'meal.view',
        'admin.surveys.index' => 'surveys.view',
        'admin.course-attendance-report.index' => 'meal.view',
        'admin.participant-attendance-summary.index' => 'meal.view',
        'admin.attendance-analytics.index' => 'meal.view',
        'admin.events.meal-report' => 'meal.view',

        'admin.hr.employees.index' => 'hr.view',
        'admin.hr.leave.index' => 'leave.view',
        'admin.hr.appraisals.index' => 'appraisals.view',
        'admin.hr.kpi-templates.index' => 'appraisals.view',
        'admin.hr.exits.index' => 'staff_exit.manage',

        'admin.suppliers.index' => 'procurement.view',
        'admin.purchase-requests.index' => 'procurement.view',
        'admin.purchase-orders.index' => 'procurement.view',

        'admin.assets.index' => 'assets.view',
        'admin.data-migrations.index' => 'settings.manage',
        'admin.settings.index' => 'settings.manage',
        'admin.platform-settings.index' => 'settings.manage',
    ];

    $navGroups = [
        [
            'label' => 'Overview',
            'colour' => 'overview',
            'items' => [
                ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high'],
                ['route' => 'admin.executive-dashboard', 'label' => 'Executive Dashboard', 'icon' => 'fa-chart-pie'],
            ],
        ],
        [
            'label' => 'People & Access',
            'colour' => 'people',
            'items' => [
                ['route' => 'admin.users.index', 'label' => 'Users', 'icon' => 'fa-users'],
                ['route' => 'admin.roles.index', 'label' => 'Roles & Permissions', 'icon' => 'fa-user-shield'],
            ],
        ],
        [
            'label' => 'Programme Management',
            'colour' => 'programme',
            'items' => [
                ['route' => 'admin.programmes.index', 'label' => 'Programmes', 'icon' => 'fa-diagram-project'],
                ['route' => 'admin.projects.index', 'label' => 'Projects', 'icon' => 'fa-folder-tree'],
                ['route' => 'admin.cohorts.index', 'label' => 'Cohorts', 'icon' => 'fa-people-group'],
                ['route' => 'admin.branches.index', 'label' => 'Branches', 'icon' => 'fa-building'],
            ],
        ],
        [
            'label' => 'Programme Delivery',
            'colour' => 'delivery',
            'items' => [
                ['route' => 'admin.elearning.courses.index', 'label' => 'Courses', 'icon' => 'fa-graduation-cap'],
                ['route' => 'admin.course-calls.index', 'label' => 'Course Calls', 'icon' => 'fa-bullhorn'],
                ['route' => 'admin.mentorship.index', 'label' => 'Mentorship', 'icon' => 'fa-handshake-angle'],
                ['route' => 'admin.jobs.index', 'label' => 'Jobs', 'icon' => 'fa-briefcase'],
                ['route' => 'admin.library.index', 'label' => 'Library', 'icon' => 'fa-book-open'],
                ['route' => 'admin.events.index', 'label' => 'Events', 'icon' => 'fa-calendar-days'],
                ['route' => 'admin.events.calendar', 'label' => 'Events Calendar', 'icon' => 'fa-calendar'],
                ['route' => 'admin.elearning.assignments.index', 'label' => 'Course Assignments', 'icon' => 'fa-user-tie'],
                ['route' => 'admin.elearning.enrolments.index', 'label' => 'Enrolments', 'icon' => 'fa-user-graduate'],
                ['route' => 'admin.elearning.learning-files.index', 'label' => 'Learning Files', 'icon' => 'fa-folder-open'],
                ['route' => 'admin.elearning.certificates.index', 'label' => 'Certificates', 'icon' => 'fa-certificate'],
                ['route' => 'admin.elearning.bulk-enrolment.create', 'label' => 'Bulk Enrolment', 'icon' => 'fa-file-import'],
            ],
        ],
        [
            'label' => 'Planning & MEAL',
            'colour' => 'meal',
            'items' => [
                ['route' => 'admin.workplans.index', 'label' => 'Workplans', 'icon' => 'fa-calendar-check'],
                ['route' => 'admin.tasks.index', 'label' => 'Tasks', 'icon' => 'fa-list-check'],
                ['route' => 'admin.deliverables.index', 'label' => 'Deliverables', 'icon' => 'fa-box-open'],
                ['route' => 'admin.indicators.index', 'label' => 'Indicators', 'icon' => 'fa-bullseye'],
                ['route' => 'admin.results-framework.index', 'label' => 'Results Framework', 'icon' => 'fa-sitemap'],
                ['route' => 'admin.meal.dashboard', 'label' => 'MEAL Dashboard', 'icon' => 'fa-chart-line'],
                ['route' => 'admin.surveys.index', 'label' => 'M&E Surveys', 'icon' => 'fa-square-poll-vertical'],
                ['route' => 'admin.course-attendance-report.index', 'label' => 'Course Attendance', 'icon' => 'fa-clipboard-user'],
                ['route' => 'admin.participant-attendance-summary.index', 'label' => 'Participant Attendance', 'icon' => 'fa-user-check'],
                ['route' => 'admin.attendance-analytics.index', 'label' => 'Attendance Analytics', 'icon' => 'fa-chart-column'],
                ['route' => 'admin.events.meal-report', 'label' => 'Event MEAL Report', 'icon' => 'fa-chart-simple'],
            ],
        ],
        [
            'label' => 'Human Resources',
            'colour' => 'hr',
            'items' => [
                ['route' => 'admin.hr.employees.index', 'label' => 'Employees', 'icon' => 'fa-id-badge'],
                ['route' => 'admin.hr.leave.index', 'label' => 'Leave', 'icon' => 'fa-calendar-minus'],
                ['route' => 'admin.hr.appraisals.index', 'label' => 'Appraisals', 'icon' => 'fa-clipboard-check'],
                ['route' => 'admin.hr.kpi-templates.index', 'label' => 'KPI Templates', 'icon' => 'fa-table-list'],
                ['route' => 'admin.hr.exits.index', 'label' => 'Staff Exits', 'icon' => 'fa-person-walking-arrow-right'],
            ],
        ],
        [
            'label' => 'Procurement',
            'colour' => 'procurement',
            'items' => [
                ['route' => 'admin.suppliers.index', 'label' => 'Suppliers', 'icon' => 'fa-truck-field'],
                ['route' => 'admin.purchase-requests.index', 'label' => 'Purchase Requests', 'icon' => 'fa-cart-plus'],
                ['route' => 'admin.purchase-orders.index', 'label' => 'Purchase Orders', 'icon' => 'fa-file-invoice-dollar'],
            ],
        ],
        [
            'label' => 'Assets & Operations',
            'colour' => 'assets',
            'items' => [
                ['route' => 'admin.assets.index', 'label' => 'Assets', 'icon' => 'fa-laptop-file'],
                ['route' => 'admin.data-migrations.index', 'label' => 'Data Migrations', 'icon' => 'fa-database'],
                ['route' => 'admin.settings.index', 'label' => 'System Settings', 'icon' => 'fa-gears'],
                ['route' => 'admin.platform-settings.index', 'label' => 'Platform Configuration', 'icon' => 'fa-sliders'],
            ],
        ],
    ];
@endphp

<aside class="eh-admin-sidebar" data-admin-sidebar="true">
    <div class="eh-admin-sidebar__brand">
        <div class="eh-admin-sidebar__logo">E360</div>
        <div class="eh-admin-sidebar__brand-copy">
            <strong>ElevateHer360</strong>
            <small>Administration</small>
        </div>
    </div>

    @if($sidebarUser)
        <a
            href="{{ \Illuminate\Support\Facades\Route::has('admin.profile') ? route('admin.profile') : '#' }}"
            class="eh-admin-sidebar__user-card"
            aria-label="Open profile"
        >
            <span class="eh-admin-sidebar__avatar">{{ $sidebarInitial }}</span>

            <span class="eh-admin-sidebar__user-copy">
                <strong>{{ $sidebarName }}</strong>
                @if($sidebarEmail !== '')
                    <small title="{{ $sidebarEmail }}">{{ $sidebarEmail }}</small>
                @endif
            </span>

            @if(\Illuminate\Support\Facades\Route::has('admin.profile'))
                <i class="fas fa-chevron-right"></i>
            @endif
        </a>
    @endif

    <nav class="eh-admin-sidebar__nav" aria-label="Administration navigation">
        @foreach($navGroups as $group)
            @php
                $visibleItems = collect($group['items'])->filter(
                    fn ($item) => $routeExists($item['route'])
                        && $canAccess($routePermissions[$item['route']] ?? null)
                );
            @endphp

            @if($visibleItems->isNotEmpty())
                <section class="eh-admin-sidebar__group eh-admin-sidebar__group--{{ $group['colour'] }}">
                    <h3>{{ $group['label'] }}</h3>

                    <div class="eh-admin-sidebar__links">
                        @foreach($visibleItems as $item)
                            <a
                                href="{{ route($item['route']) }}"
                                class="{{ request()->routeIs($item['route']) ? 'active' : '' }}"
                            >
                                <i class="fas {{ $item['icon'] }}"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </nav>
</aside>
