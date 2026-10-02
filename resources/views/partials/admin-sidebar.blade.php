@php
    use Illuminate\Support\Facades\Route;

    $sidebarUser = auth()->user();

    $sidebarName = $sidebarUser?->name
        ?: trim(($sidebarUser?->first_name ?? '') . ' ' . ($sidebarUser?->last_name ?? ''))
        ?: 'Administrator';

    $sidebarEmail = $sidebarUser?->email ?? '';
    $sidebarInitial = strtoupper(mb_substr(trim($sidebarName), 0, 1));

    $isSuperAdmin = $sidebarUser
        && method_exists($sidebarUser, 'isSuperAdmin')
        && $sidebarUser->isSuperAdmin();

    $isInstructor = $sidebarUser
        && ! $isSuperAdmin
        && method_exists($sidebarUser, 'hasAnyRole')
        && $sidebarUser->hasAnyRole(['instructor', 'trainer']);

    $brandSettings = app(\App\Services\SettingsService::class);
    $brandLogoPath = $brandSettings->get('branding.logo_path');

    $brandLogoUrl = $brandLogoPath && Route::has('branding.asset')
        ? route('branding.asset', [
            'type' => 'logo',
            'v' => md5((string) $brandLogoPath),
        ])
        : null;

    $routeExists = static fn (?string $name): bool =>
        filled($name) && Route::has($name);

    $userHasAnyRole = static function (array $roles) use ($sidebarUser, $isSuperAdmin): bool {
        if (! $sidebarUser || $roles === [] || $isSuperAdmin) {
            return true;
        }

        return method_exists($sidebarUser, 'hasAnyRole')
            && $sidebarUser->hasAnyRole($roles);
    };

    $userHasAnyPermission = static function (array $permissions) use ($sidebarUser, $isSuperAdmin): bool {
        if (! $sidebarUser || ! $sidebarUser->isActive()) {
            return false;
        }

        if ($isSuperAdmin || $permissions === []) {
            return true;
        }

        try {
            if (method_exists($sidebarUser, 'hasAnyPermission')) {
                return $sidebarUser->hasAnyPermission($permissions);
            }

            foreach ($permissions as $permission) {
                if ($sidebarUser->can($permission)) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    };

    $canAccess = static function (array $item) use (
        $sidebarUser,
        $isSuperAdmin,
        $userHasAnyRole,
        $userHasAnyPermission
    ): bool {
        if (! $sidebarUser || ! $sidebarUser->isActive()) {
            return false;
        }

        if ($isSuperAdmin) {
            return true;
        }

        $roles = array_values(array_filter($item['roles'] ?? []));
        $excludeRoles = array_values(array_filter($item['exclude_roles'] ?? []));
        $permissions = array_values(array_filter($item['permissions'] ?? []));

        if (
            $excludeRoles !== []
            && ! $isSuperAdmin
            && method_exists($sidebarUser, 'hasAnyRole')
            && $sidebarUser->hasAnyRole($excludeRoles)
        ) {
            return false;
        }

        if ($roles !== [] && ! $userHasAnyRole($roles)) {
            return false;
        }

        return $userHasAnyPermission($permissions);
    };

    $navGroups = [
        [
            'label' => 'Overview',
            'colour' => 'overview',
            'items' => [
                [
                    'route' => 'admin.dashboard',
                    'label' => 'Dashboard',
                    'icon' => 'fa-gauge-high',
                    'permissions' => [],
                ],
                ['route'=>'calendar.index','label'=>'Calendar','icon'=>'fa-calendar-days','permissions'=>[]],
            ],
        ],
        [
            'label' => 'Workspaces',
            'colour' => 'delivery',
            'items' => [
                [
                    'route' => 'admin.workspace.learning',
                    'label' => 'Learning',
                    'icon' => 'fa-graduation-cap',
                    'permissions' => ['courses.view', 'courses.edit', 'students.view', 'students.edit'],
                ],
                [
                    'route' => 'admin.workspace.planning-meal',
                    'label' => 'Planning & MEAL',
                    'icon' => 'fa-chart-line',
                    'permissions' => [
                        'workplans.view',
                        'workplans.edit',
                        'workplans.approve',
                        'tasks.manage',
                        'indicators.view',
                        'meal.view',
                        'meal.manage',
                        'reports.view',
                    ],
                ],
                [
                    'route' => 'admin.workspace.mentorship',
                    'label' => 'Mentorship',
                    'icon' => 'fa-handshake',
                    'permissions' => ['mentors.manage', 'mentorship.match'],
                ],
                [
                    'route' => 'admin.workspace.jobs',
                    'label' => 'Jobs & Opportunities',
                    'icon' => 'fa-briefcase',
                    'permissions' => ['jobs.manage', 'hr.view', 'hr.manage'],
                ],
                [
                    'route' => 'admin.workspace.reports',
                    'label' => 'Reports',
                    'icon' => 'fa-chart-column',
                    'permissions' => ['reports.view', 'meal.view', 'meal.manage'],
                ],
            ],
        ],
        [
            'label' => 'People & Performance',
            'colour' => 'hr',
            'items' => [
                [
                    'route' => 'staff.performance.index',
                    'label' => 'Appraisals & Performance',
                    'icon' => 'fa-clipboard-check',
                    'permissions' => [],
                ],
                [
                    'route' => 'staff.tasks.index',
                    'label' => 'My Tasks',
                    'icon' => 'fa-list-check',
                    'permissions' => [],
                    'active' => ['staff.tasks.*'],
                ],
                [
                    'route' => 'staff.kpis.index',
                    'label' => 'My KPIs',
                    'icon' => 'fa-bullseye',
                    'permissions' => [],
                    'active' => ['staff.kpis.*'],
                ],
                [
                    'route' => 'admin.hr.employees.index',
                    'label' => 'Employees',
                    'icon' => 'fa-id-badge',
                    'permissions' => ['hr.view', 'hr.manage'],
                ],
                [
                    'route' => 'admin.hr.leave.index',
                    'label' => 'Leave',
                    'icon' => 'fa-calendar-minus',
                    'permissions' => [],
                ],
                [
                    'route' => 'admin.users.index',
                    'label' => 'Users',
                    'icon' => 'fa-users',
                    'permissions' => ['users.edit'],
                ],
                [
                    'route' => 'admin.roles.index',
                    'label' => 'Roles & Permissions',
                    'icon' => 'fa-user-shield',
                    'permissions' => ['roles.manage', 'permissions.manage'],
                ],
            ],
        ],
        [
            'label' => 'Programme',
            'colour' => 'programme',
            'items' => [
                [
                    'route' => 'admin.programmes.index',
                    'label' => 'Programmes',
                    'icon' => 'fa-diagram-project',
                    'permissions' => ['programmes.manage'],
                ],
                [
                    'route' => 'admin.elearning.timetable.index',
                    'label' => 'Course Timetables',
                    'icon' => 'fa-calendar-days',
                    'permissions' => [],
                    'active' => ['admin.elearning.timetable.*'],
                ],
                [
                    'route' => 'admin.cms.index',
                    'label' => 'Frontend CMS',
                    'icon' => 'fa-file-pen',
                    'permissions' => ['cms.manage'],
                    'active' => ['admin.cms.*'],
                ],
                [
                    'route' => 'admin.projects.index',
                    'label' => 'Projects',
                    'icon' => 'fa-folder-tree',
                    'permissions' => ['programmes.manage'],
                ],
                [
                    'route' => 'admin.cohorts.index',
                    'label' => 'Cohorts',
                    'icon' => 'fa-people-group',
                    'permissions' => ['cohorts.manage'],
                    'roles' => ['administrator', 'super-administrator', 'super-admin'],
                ],
                [
                    'route' => 'admin.branches.index',
                    'label' => 'Branches',
                    'icon' => 'fa-building',
                    'permissions' => ['programmes.manage'],
                ],
                [
                    'route' => 'admin.events.index',
                    'label' => 'Events',
                    'icon' => 'fa-calendar-days',
                    'permissions' => ['calendar.manage'],
                ],
                [
                    'route' => 'certificates.recommendations.index',
                    'label' => 'Certificates',
                    'icon' => 'fa-award',
                    'permissions' => [],
                    'roles' => \App\Services\CertificateRecommendationService::RECOMMENDER_ROLES,
                    'active' => ['certificates.recommendations.*', 'admin.elearning.certificates.*'],
                ],
                [
                    'route' => 'admin.library.index',
                    'label' => 'Library',
                    'icon' => 'fa-book-open',
                    'permissions' => ['library.manage'],
                ],
            ],
        ],
        [
            'label' => 'Administration',
            'colour' => 'assets',
            'items' => [
                [
                    'route' => 'admin.import-centre.index',
                    'label' => 'Import Centre',
                    'icon' => 'fa-file-import',
                    'permissions' => ['settings.manage'],
                ],
                [
                    'route' => 'admin.procurement.requests.index',
                    'label' => 'Procurement',
                    'icon' => 'fa-cart-shopping',
                    'permissions' => [],
                ],
                [
                    'route' => 'admin.assets.index',
                    'label' => 'Assets',
                    'icon' => 'fa-laptop-file',
                    'permissions' => ['assets.view', 'assets.manage', 'assets.dispose'],
                ],
                [
                    'route' => 'admin.audit-logs.index',
                    'label' => 'Audit Logs',
                    'icon' => 'fa-clock-rotate-left',
                    'permissions' => ['reports.view'],
                ],
                [
                    'route' => 'admin.notifications.index',
                    'label' => 'Notifications',
                    'icon' => 'fa-bell',
                    'permissions' => ['reports.view'],
                ],
                [
                    'route' => 'admin.platform-settings.index',
                    'label' => 'Platform Configuration',
                    'icon' => 'fa-sliders',
                    'permissions' => ['settings.manage'],
                ],
                [
                    'route' => 'admin.settings.index',
                    'label' => 'System Settings',
                    'icon' => 'fa-gears',
                    'permissions' => ['settings.manage'],
                ],
            ],
        ],
        [
            'label' => 'Account',
            'colour' => 'people',
            'items' => [
                [
                    'route' => 'admin.profile.edit',
                    'label' => 'Profile',
                    'icon' => 'fa-user',
                    'permissions' => [],
                ],
            ],
        ],
    ];

    if ($isInstructor) {
        $assignedCourses = $sidebarUser->instructedCourses()
            ->orderBy('title')
            ->get();

        $routeCourse = request()->route('course');

        $activeCourse = $routeCourse instanceof \App\Models\Course
            ? $routeCourse
            : $assignedCourses->first();

        $navGroups = [
            [
                'label' => 'Instructor',
                'colour' => 'delivery',
                'items' => [
                    [
                        'route' => 'admin.dashboard',
                        'label' => 'Dashboard',
                        'icon' => 'fa-gauge-high',
                        'permissions' => [],
                    ],
                    [
                        'route' => 'admin.my-courses',
                        'label' => 'My Courses',
                        'icon' => 'fa-chalkboard-user',
                        'permissions' => [],
                    ],
                    [
                        'route' => 'admin.elearning.timetable.index',
                        'label' => 'Course Timetables',
                        'icon' => 'fa-calendar-days',
                        'permissions' => [],
                    ],
                    [
                        'route' => 'calendar.index',
                        'label' => 'Calendar',
                        'icon' => 'fa-calendar-days',
                        'permissions' => [],
                    ],
                    [
                        'route' => 'staff.tasks.index',
                        'label' => 'My Tasks',
                        'icon' => 'fa-list-check',
                        'permissions' => [],
                        'active' => ['staff.tasks.*'],
                    ],
                    [
                        'route' => 'staff.kpis.index',
                        'label' => 'My KPIs',
                        'icon' => 'fa-bullseye',
                        'permissions' => [],
                        'active' => ['staff.kpis.*'],
                    ],
                    [
                        'route' => 'certificates.recommendations.index',
                        'label' => 'Certificate Recommendations',
                        'icon' => 'fa-award',
                        'permissions' => [],
                        'active' => ['certificates.recommendations.*'],
                    ],
                ],
            ],
        ];

        if ($activeCourse && Route::has('instructor.courses.manage')) {
            $manageUrl = route('instructor.courses.manage', $activeCourse);

            $navGroups[] = [
                'label' => 'Course Delivery',
                'colour' => 'delivery',
                'items' => [
                    ['url' => $manageUrl.'?tab=modules', 'label' => 'Modules', 'icon' => 'fa-layer-group'],
                    ['url' => $manageUrl.'?tab=timetable', 'label' => 'Timetable', 'icon' => 'fa-calendar-days'],
                    ['url' => $manageUrl.'?tab=lessons', 'label' => 'Lessons', 'icon' => 'fa-book-open'],
                    ['url' => $manageUrl.'?tab=materials', 'label' => 'Learning Materials', 'icon' => 'fa-folder-open'],
                    ['url' => $manageUrl.'?tab=assignments', 'label' => 'Assignments', 'icon' => 'fa-list-check'],
                    ['url' => $manageUrl.'?tab=quizzes', 'label' => 'Quizzes', 'icon' => 'fa-circle-question'],
                    ['url' => $manageUrl.'?tab=exams', 'label' => 'Exams', 'icon' => 'fa-file-signature'],
                    ['url' => $manageUrl.'?tab=announcements', 'label' => 'Announcements', 'icon' => 'fa-bullhorn'],
                    ['url' => $manageUrl.'?tab=participants', 'label' => 'Course Participants', 'icon' => 'fa-users'],
                    ['url' => $manageUrl.'?tab=progress', 'label' => 'Participant Progress', 'icon' => 'fa-chart-line'],
                    ['url' => route('certificates.recommendations.create', ['course_id' => $activeCourse->id]), 'label' => 'Recommend Certificates', 'icon' => 'fa-award'],
                ],
            ];
        }

        $navGroups[] = [
            'label' => 'Opportunities & Support',
            'colour' => 'people',
            'items' => [
                [
                    'route' => 'mentorship.dashboard',
                    'label' => 'Mentorship',
                    'icon' => 'fa-handshake',
                    'permissions' => [],
                ],
                [
                    'route' => 'library.index',
                    'label' => 'Library',
                    'icon' => 'fa-book-open',
                    'permissions' => [],
                ],
                [
                    'route' => 'jobs.index',
                    'label' => 'Jobs',
                    'icon' => 'fa-briefcase',
                    'permissions' => [],
                ],
            ],
        ];

        $navGroups[] = [
            'label' => 'Account',
            'colour' => 'assets',
            'items' => [
                [
                    'route' => 'admin.profile.edit',
                    'label' => 'Profile',
                    'icon' => 'fa-user',
                    'permissions' => [],
                ],
            ],
        ];
    }

    $isActiveItem = static function (array $item): bool {
        if (! empty($item['active']) && is_array($item['active'])) {
            foreach ($item['active'] as $pattern) {
                if (request()->routeIs($pattern)) {
                    return true;
                }
            }
        }

        if (! empty($item['route'])) {
            return request()->routeIs($item['route']);
        }

        return false;
    };
@endphp

<aside class="eh-admin-sidebar" data-admin-sidebar="true">
    <div class="eh-admin-sidebar__brand">
        <a href="{{ route('admin.dashboard') }}" class="eh-admin-sidebar__logo" aria-label="ElevateHer360 dashboard">
            @if($brandLogoUrl)
                <img
                    src="{{ $brandLogoUrl }}"
                    alt="ElevateHer360 logo"
                    class="eh-dynamic-brand-logo"
                >
            @else
                <span>E360</span>
            @endif
        </a>

        <div class="eh-admin-sidebar__brand-copy">
            <strong>ElevateHer360</strong>
            <small>{{ $isInstructor ? 'Instructor / Trainer' : 'Administration' }}</small>
        </div>
    </div>

    @if($sidebarUser)
        <a
            href="{{ Route::has('admin.profile.edit') ? route('admin.profile.edit') : '#' }}"
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

            @if(Route::has('admin.profile.edit'))
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            @endif
        </a>
    @endif

    <nav class="eh-admin-sidebar__nav" aria-label="Administration navigation">
        @foreach($navGroups as $group)
            @php
                $visibleItems = collect($group['items'])
                    ->filter(function (array $item) use ($routeExists, $canAccess): bool {
                        if (! empty($item['url'])) {
                            return true;
                        }

                        return $routeExists($item['route'] ?? null)
                            && $canAccess($item);
                    })
                    ->values();
            @endphp

            @if($visibleItems->isNotEmpty())
                <section class="eh-admin-sidebar__group eh-admin-sidebar__group--{{ $group['colour'] }}">
                    <h3>{{ $group['label'] }}</h3>

                    <div class="eh-admin-sidebar__links">
                        @foreach($visibleItems as $item)
                            @php
                                $href = $item['url'] ?? route($item['route']);
                                $active = $isActiveItem($item);
                            @endphp

                            <a
                                href="{{ $href }}"
                                class="{{ $active ? 'active' : '' }}"
                                @if($active) aria-current="page" @endif
                            >
                                <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </nav>

    @if(Route::has('admin.logout'))
        <div class="eh-admin-sidebar__footer">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf

                <button type="submit" class="eh-admin-sidebar__logout">
                    <i class="fas fa-right-from-bracket" aria-hidden="true"></i>
                    <span>Sign out</span>
                </button>
            </form>
        </div>
    @endif
</aside>
