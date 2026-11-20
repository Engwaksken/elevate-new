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

    // HR self-service badges: policies to acknowledge and interviews still to score.
    $hrPolicyPending = 0;
    $hrPanelPending = 0;
    $hrHasPanels = false;
    if ($sidebarUser && \Illuminate\Support\Facades\Schema::hasTable('hr_policy_assignments')) {
        try {
            $hrPolicyPending = \App\Models\HrPolicyAssignment::where('user_id', $sidebarUser->id)->whereNull('acknowledged_at')
                ->whereHas('policy', fn ($q) => $q->where('is_active', true)->where('requires_acknowledgement', true))->count();
            $hrPanelPending = \App\Models\RecruitmentInterviewPanelist::where('user_id', $sidebarUser->id)->whereNull('submitted_at')
                ->whereHas('interview', fn ($q) => $q->where('status', 'scheduled'))->count();
            $hrHasPanels = $hrPanelPending > 0 || \App\Models\RecruitmentInterviewPanelist::where('user_id', $sidebarUser->id)->exists();
        } catch (\Throwable) {
        }
    }

    // Pending appointment requests addressed to this instructor (sidebar badge).
    $appointmentPending = 0;
    if ($sidebarUser && ($isInstructor || $isSuperAdmin) && Route::has('instructor.appointments.index')) {
        try {
            $appointmentPending = \App\Models\InstructorAppointment::query()
                ->where('instructor_user_id', $sidebarUser->id)
                ->where('status', \App\Models\InstructorAppointment::PENDING)
                ->count();
        } catch (\Throwable) {
            $appointmentPending = 0;
        }
    }

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

        // Items can carry their own precomputed rule (e.g. "supervises staff").
        if (array_key_exists('visible', $item) && ! $item['visible']) {
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

    /*
     * Navigation groups, organised by what people come to do:
     *   Home          - dashboard, calendar, chat (everyone)
     *   My Work       - personal self-service every staff member uses (open by default)
     *   Approvals     - queues people act on for others
     *   then one group per area of the organisation, and System Administration last.
     * 'keywords' feed the "Find a page" box, so people can search by the words they use.
     */
    $myWorkItems = [
        ['route' => 'staff.tasks.index', 'label' => 'My Tasks', 'icon' => 'fa-list-check', 'permissions' => [], 'active' => ['staff.tasks.*'], 'keywords' => 'to do work assignments'],
        ['route' => 'staff.kpis.index', 'label' => 'My KPIs', 'icon' => 'fa-bullseye', 'permissions' => [], 'active' => ['staff.kpis.*'], 'keywords' => 'targets indicators contract'],
        ['route' => 'staff.performance.index', 'label' => 'My Appraisals', 'icon' => 'fa-clipboard-check', 'permissions' => [], 'active' => ['staff.performance.*', 'staff.appraisals.*'], 'keywords' => 'performance review evaluation kra'],
        ['route' => 'hr.leave.index', 'label' => 'My Leave', 'icon' => 'fa-umbrella-beach', 'permissions' => [], 'active' => ['hr.leave.*'], 'keywords' => 'holiday time off annual sick absence'],
        ['route' => 'staff.hr.index', 'label' => 'My HR', 'icon' => 'fa-file-shield', 'permissions' => [], 'active' => ['staff.hr.*'], 'badge' => $hrPolicyPending, 'keywords' => 'policies acknowledge my documents national id certificates upload'],
        ['route' => 'staff.interviews.index', 'label' => 'My Interview Panels', 'icon' => 'fa-user-tie', 'permissions' => [], 'active' => ['staff.interviews.*'], 'badge' => $hrPanelPending, 'visible' => $hrHasPanels, 'keywords' => 'recruitment score candidates'],
        ['route' => 'staff.purchase-requests.index', 'label' => 'My Purchase Requests', 'icon' => 'fa-cart-plus', 'permissions' => [], 'active' => ['staff.purchase-requests.*'], 'keywords' => 'procurement buy requisition order'],
        ['route' => 'staff.documents.index', 'label' => 'Document Libraries', 'icon' => 'fa-folder-tree', 'permissions' => [], 'active' => ['staff.documents.*'], 'keywords' => 'files sharepoint folders department shared'],
        ['route' => 'staff.support.index', 'label' => 'Help & Support', 'icon' => 'fa-life-ring', 'permissions' => [], 'active' => ['staff.support.*'], 'keywords' => 'it ticket laptop problem request helpdesk'],
    ];

    $accountGroup = [
        'label' => 'Account',
        'colour' => 'overview',
        'items' => [
            ['route' => 'admin.profile.edit', 'label' => 'My Profile', 'icon' => 'fa-user', 'permissions' => [], 'keywords' => 'password account settings'],
        ],
    ];

    $navGroups = [
        [
            'label' => 'Home',
            'colour' => 'overview',
            'items' => [
                ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'permissions' => [], 'keywords' => 'home overview'],
                ['route' => 'calendar.index', 'label' => 'Calendar', 'icon' => 'fa-calendar-days', 'permissions' => [], 'keywords' => 'schedule meetings'],
                ['route' => 'admin.chat.index', 'label' => 'Chat', 'icon' => 'fa-comments', 'permissions' => [], 'active' => ['admin.chat.*'], 'keywords' => 'messages colleagues'],
            ],
        ],
        [
            'label' => 'My Work',
            'colour' => 'people',
            'open' => true,
            'items' => $myWorkItems,
        ],
        [
            'label' => 'Approvals & Queues',
            'colour' => 'delivery',
            'items' => [
                ['route' => 'admin.hr.leave.index', 'label' => 'Leave Approvals', 'icon' => 'fa-calendar-minus', 'permissions' => [],
                    // Supervisors (own team), HR and administrators only.
                    'visible' => \App\Support\LeaveApprovalAccess::canAccess($sidebarUser), 'keywords' => 'approve team leave requests'],
                ['route' => 'it-support.tickets.index', 'label' => 'IT Support Queue', 'icon' => 'fa-headset', 'permissions' => [],
                    'roles' => [...\App\Services\ItSupportTicketService::SUPPORT_ROLES, 'administrator', 'Administrator'], 'active' => ['it-support.tickets.*'], 'keywords' => 'tickets helpdesk'],
                ['route' => 'certificates.recommendations.index', 'label' => 'Certificates', 'icon' => 'fa-award', 'permissions' => [],
                    'roles' => \App\Services\CertificateRecommendationService::RECOMMENDER_ROLES, 'active' => ['certificates.recommendations.*', 'admin.elearning.certificates.*'], 'keywords' => 'recommend approve issue'],
            ],
        ],
        [
            'label' => 'Programmes & Learning',
            'colour' => 'programme',
            'items' => [
                ['route' => 'admin.workspace.learning', 'label' => 'Learning Workspace', 'icon' => 'fa-graduation-cap',
                    'permissions' => ['courses.view', 'courses.edit', 'students.view', 'students.edit', 'course_calls.view'], 'keywords' => 'courses elearning enrolment calls applications'],
                ['route' => 'admin.elearning.timetable.index', 'label' => 'Course Timetables', 'icon' => 'fa-calendar-week', 'permissions' => [], 'active' => ['admin.elearning.timetable.*'], 'keywords' => 'classes sessions schedule'],
                ['route' => 'instructor.appointments.index', 'label' => 'Appointments', 'icon' => 'fa-calendar-check', 'permissions' => [], 'roles' => ['instructor', 'trainer'],
                    'active' => ['instructor.appointments.*'], 'badge' => $appointmentPending, 'keywords' => 'bookings'],
                ['route' => 'admin.programmes.index', 'label' => 'Programmes', 'icon' => 'fa-diagram-project', 'permissions' => ['programmes.manage']],
                ['route' => 'admin.projects.index', 'label' => 'Projects', 'icon' => 'fa-folder-tree', 'permissions' => ['programmes.manage']],
                ['route' => 'admin.cohorts.index', 'label' => 'Cohorts', 'icon' => 'fa-people-group', 'permissions' => ['cohorts.manage'], 'roles' => ['administrator', 'super-administrator', 'super-admin'], 'keywords' => 'intake class group'],
                ['route' => 'admin.branches.index', 'label' => 'Branches', 'icon' => 'fa-building', 'permissions' => ['programmes.manage'], 'keywords' => 'locations offices'],
                ['route' => 'admin.events.index', 'label' => 'Events', 'icon' => 'fa-calendar-plus', 'permissions' => ['calendar.manage'], 'keywords' => 'workshops invitations registrations check-in'],
                ['route' => 'admin.library.index', 'label' => 'Library', 'icon' => 'fa-book-open', 'permissions' => ['library.manage'], 'keywords' => 'books resources ebooks'],
            ],
        ],
        [
            'label' => 'Participants',
            'colour' => 'meal',
            'items' => [
                ['route' => 'admin.participants.index', 'label' => 'Participants', 'icon' => 'fa-user-graduate', 'permissions' => ['users.view'], 'keywords' => 'students beneficiaries learners'],
                ['route' => 'admin.participant-goals.index', 'label' => 'Participant Goals', 'icon' => 'fa-flag', 'permissions' => ['users.view']],
                ['route' => 'admin.participant-feedback.index', 'label' => 'Participant Feedback', 'icon' => 'fa-comment-dots', 'permissions' => [], 'roles' => \App\Support\ParticipantFeedbackAccess::ROLES, 'keywords' => 'evaluation survey responses'],
                ['route' => 'admin.workspace.mentorship', 'label' => 'Mentorship', 'icon' => 'fa-handshake', 'permissions' => ['mentors.manage', 'mentorship.match'], 'keywords' => 'mentors mentees matching'],
                ['route' => 'admin.workspace.jobs', 'label' => 'Jobs & Opportunities', 'icon' => 'fa-briefcase', 'permissions' => ['jobs.manage', 'hr.view', 'hr.manage'], 'keywords' => 'job board placements outcomes'],
            ],
        ],
        [
            'label' => 'Planning & Reports',
            'colour' => 'meal',
            'items' => [
                ['route' => 'admin.workspace.planning-meal', 'label' => 'Planning & MEAL', 'icon' => 'fa-chart-line',
                    'permissions' => ['workplans.view', 'workplans.edit', 'workplans.approve', 'tasks.manage', 'indicators.view', 'meal.view', 'meal.manage', 'reports.view'],
                    'keywords' => 'workplans indicators milestones activities results framework monitoring evaluation'],
                ['route' => 'admin.workspace.reports', 'label' => 'Reports', 'icon' => 'fa-chart-column', 'permissions' => ['reports.view', 'meal.view', 'meal.manage'], 'keywords' => 'analytics export statistics'],
            ],
        ],
        [
            'label' => 'HR & People',
            'colour' => 'hr',
            'items' => [
                ['route' => 'admin.hr.dashboard', 'label' => 'HR Operations', 'icon' => 'fa-people-roof', 'permissions' => ['hr.view', 'hr.manage'],
                    'active' => ['admin.hr.dashboard', 'admin.hr.policies.*', 'admin.hr.compliance.*', 'admin.hr.appraisals.*', 'admin.hr.kpi-templates.*', 'admin.hr.exits.*'],
                    'keywords' => 'policies compliance staff documents reminders appraisals exits'],
                ['route' => 'admin.hr.recruitment.index', 'label' => 'Recruitment', 'icon' => 'fa-user-plus', 'permissions' => ['hr.view', 'hr.manage'], 'active' => ['admin.hr.recruitment.*'], 'keywords' => 'vacancies hiring applicants shortlist interviews careers embed'],
                ['route' => 'admin.hr.employees.index', 'label' => 'Employee Directory', 'icon' => 'fa-id-badge', 'permissions' => ['hr.view', 'hr.manage'], 'active' => ['admin.hr.employees.*'], 'keywords' => 'staff list contracts'],
                ['route' => 'admin.departments.index', 'label' => 'Departments', 'icon' => 'fa-sitemap', 'permissions' => [],
                    // HR and administrators (App\Support\MasterListAccess).
                    'visible' => \App\Support\MasterListAccess::canManageDepartments($sidebarUser)],
            ],
        ],
        [
            'label' => 'Finance & Operations',
            'colour' => 'procurement',
            'items' => [
                ['route' => 'admin.procurement.requests.index', 'label' => 'Procurement', 'icon' => 'fa-cart-shopping', 'permissions' => ['procurement.view'], 'active' => ['admin.procurement.*'], 'keywords' => 'purchase requests suppliers quotations orders receipts'],
                ['route' => 'admin.assets.index', 'label' => 'Assets', 'icon' => 'fa-laptop-file', 'permissions' => ['assets.view', 'assets.manage', 'assets.dispose'], 'keywords' => 'equipment inventory laptops maintenance'],
                ['route' => 'admin.funding-sources.index', 'label' => 'Funding Sources', 'icon' => 'fa-hand-holding-dollar', 'permissions' => [],
                    // Procurement, finance and administrators.
                    'visible' => \App\Support\MasterListAccess::canManageFundingSources($sidebarUser), 'keywords' => 'donors grants'],
            ],
        ],
        [
            'label' => 'System Administration',
            'colour' => 'assets',
            'items' => [
                ['route' => 'admin.users.index', 'label' => 'Users', 'icon' => 'fa-users', 'permissions' => ['users.edit'], 'keywords' => 'accounts logins staff create user'],
                ['route' => 'admin.roles.index', 'label' => 'Roles & Permissions', 'icon' => 'fa-user-shield', 'permissions' => ['roles.manage', 'permissions.manage'], 'keywords' => 'access rights'],
                ['route' => 'admin.notifications.index', 'label' => 'Notifications', 'icon' => 'fa-bell', 'permissions' => ['reports.view'], 'keywords' => 'announcements send messages'],
                ['route' => 'admin.cms.index', 'label' => 'Frontend CMS', 'icon' => 'fa-file-pen', 'permissions' => ['cms.manage'], 'active' => ['admin.cms.*'], 'keywords' => 'website pages public site'],
                ['route' => 'admin.import-centre.index', 'label' => 'Import Centre', 'icon' => 'fa-file-import', 'permissions' => ['settings.manage'], 'keywords' => 'upload excel csv bulk migrate'],
                ['route' => 'admin.audit-logs.index', 'label' => 'Audit Logs', 'icon' => 'fa-clock-rotate-left', 'permissions' => ['reports.view'], 'keywords' => 'history activity security'],
                ['route' => 'admin.account-deletions.index', 'label' => 'Account Deletions', 'icon' => 'fa-user-xmark', 'permissions' => ['users.delete'], 'keywords' => 'delete requests privacy'],
                ['route' => 'admin.platform-settings.index', 'label' => 'Platform Configuration', 'icon' => 'fa-sliders', 'permissions' => ['settings.manage'], 'keywords' => 'branding ai email'],
                ['route' => 'admin.settings.index', 'label' => 'System Settings', 'icon' => 'fa-gears', 'permissions' => ['settings.manage'], 'keywords' => 'backup maintenance configuration'],
            ],
        ],
        $accountGroup,
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
                'label' => 'Teaching',
                'colour' => 'delivery',
                'open' => true,
                'items' => [
                    ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'permissions' => [], 'keywords' => 'home overview'],
                    ['route' => 'admin.my-courses', 'label' => 'My Courses', 'icon' => 'fa-chalkboard-user', 'permissions' => [], 'keywords' => 'classes teaching'],
                    ['route' => 'instructor.appointments.index', 'label' => 'Appointments', 'icon' => 'fa-calendar-check', 'permissions' => [], 'active' => ['instructor.appointments.*'], 'badge' => $appointmentPending, 'keywords' => 'bookings'],
                    ['route' => 'admin.elearning.timetable.index', 'label' => 'Course Timetables', 'icon' => 'fa-calendar-week', 'permissions' => [], 'keywords' => 'classes sessions schedule'],
                    ['route' => 'calendar.index', 'label' => 'Calendar', 'icon' => 'fa-calendar-days', 'permissions' => []],
                    ['route' => 'admin.chat.index', 'label' => 'Chat', 'icon' => 'fa-comments', 'permissions' => [], 'active' => ['admin.chat.*'], 'keywords' => 'messages participants colleagues'],
                    ['route' => 'certificates.recommendations.index', 'label' => 'Certificate Recommendations', 'icon' => 'fa-award', 'permissions' => [], 'active' => ['certificates.recommendations.*']],
                ],
            ],
        ];

        if ($activeCourse && Route::has('instructor.courses.manage')) {
            $manageUrl = route('instructor.courses.manage', $activeCourse);

            $navGroups[] = [
                'label' => 'Course Delivery',
                'colour' => 'programme',
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
            'label' => 'My Work',
            'colour' => 'people',
            'items' => $myWorkItems,
        ];

        $navGroups[] = [
            'label' => 'Opportunities',
            'colour' => 'meal',
            'items' => [
                // Instructors use mentorship as mentees (find mentors, sessions, goals).
                ['route' => 'mentorship.dashboard', 'label' => 'Mentorship', 'icon' => 'fa-handshake', 'permissions' => [], 'active' => ['mentorship.dashboard', 'mentorship.mentors.*']],
                ['route' => 'library.index', 'label' => 'Library', 'icon' => 'fa-book-open', 'permissions' => []],
                ['route' => 'jobs.index', 'label' => 'Jobs', 'icon' => 'fa-briefcase', 'permissions' => []],
                ['route' => 'it-support.tickets.index', 'label' => 'IT Support Queue', 'icon' => 'fa-headset', 'permissions' => [],
                    'roles' => [...\App\Services\ItSupportTicketService::SUPPORT_ROLES, 'administrator', 'Administrator'], 'active' => ['it-support.tickets.*']],
            ],
        ];

        $navGroups[] = $accountGroup;
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

    <div class="eh-admin-sidebar__search" role="search">
        <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
        <input type="search" placeholder="Find a page..." aria-label="Find a page in the menu" autocomplete="off" data-admin-sidebar-search>
    </div>
    <p class="eh-admin-sidebar__no-match" data-admin-sidebar-no-match hidden>No page matches. Try another word.</p>

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

            @php
                $groupActive = $visibleItems->contains(fn (array $item): bool => $isActiveItem($item));
                $groupOpen = $groupActive || ! empty($group['open']);
                $groupId = 'admin-sidebar-group-' . \Illuminate\Support\Str::slug($group['label']);
            @endphp
            @if($visibleItems->isNotEmpty())
                <section class="eh-admin-sidebar__group eh-admin-sidebar__group--{{ $group['colour'] }}" data-admin-sidebar-group="{{ $groupId }}" @if($groupActive) data-has-active @endif>
                    <h3><button type="button" class="eh-admin-sidebar__group-toggle" aria-expanded="{{ $groupOpen ? 'true' : 'false' }}" aria-controls="{{ $groupId }}" data-admin-sidebar-group-toggle>{{ $group['label'] }}<i class="fas fa-chevron-down" aria-hidden="true"></i></button></h3>

                    <div id="{{ $groupId }}" class="eh-admin-sidebar__links" @if(! $groupOpen) hidden @endif>
                        @foreach($visibleItems as $item)
                            @php
                                $href = $item['url'] ?? route($item['route']);
                                $active = $isActiveItem($item);
                            @endphp

                            <a
                                href="{{ $href }}"
                                class="{{ $active ? 'active' : '' }}"
                                title="{{ $item['label'] }}"
                                data-admin-sidebar-item
                                data-search="{{ \Illuminate\Support\Str::lower($item['label'].' '.($item['keywords'] ?? '').' '.$group['label']) }}"
                                @if($active) aria-current="page" @endif
                            >
                                <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>
                                <span>{{ $item['label'] }}</span>
                                @if(! empty($item['badge']))
                                    <span class="eh-admin-sidebar__badge" style="flex:0 0 auto;width:auto;margin-left:auto;min-width:20px;padding:1px 7px;border-radius:999px;background:#b42318;color:#fff;font-size:.68rem;font-weight:800;line-height:18px;text-align:center" aria-label="{{ $item['badge'] }} pending">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                                @endif
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

                <button type="submit" class="eh-admin-sidebar__logout" title="Sign out">
                    <i class="fas fa-right-from-bracket" aria-hidden="true"></i>
                    <span>Sign out</span>
                </button>
            </form>
        </div>
    @endif
</aside>

<style>
.eh-admin-sidebar__search{position:relative;margin:4px 14px 8px}
.eh-admin-sidebar__search i{position:absolute;left:11px;top:50%;transform:translateY(-50%);font-size:.75rem;color:rgba(255,255,255,.7);pointer-events:none}
.eh-admin-sidebar__search input{width:100%;height:36px;min-height:36px;padding:0 10px 0 30px;border:1px solid rgba(255,255,255,.28);border-radius:8px;background:rgba(255,255,255,.12);color:#fff;font-size:.8rem}
.eh-admin-sidebar__search input::placeholder{color:rgba(255,255,255,.72)}
.eh-admin-sidebar__search input:focus{outline:2px solid #d4af37;outline-offset:1px;background:rgba(255,255,255,.2)}
.eh-admin-sidebar__no-match{margin:0 16px 8px;color:rgba(255,255,255,.85);font-size:.76rem}
.admin-sidebar-collapsed .eh-admin-sidebar__search,.admin-sidebar-collapsed .eh-admin-sidebar__no-match{display:none}
</style>
<script>
(function () {
    // Remember which groups each person opened or closed, and let them search the menu.
    var KEY = 'eh-admin-sidebar-groups';
    var groups = Array.prototype.slice.call(document.querySelectorAll('[data-admin-sidebar-group]'));
    var saved = {};
    try { saved = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) { saved = {}; }

    function setOpen(group, open) {
        var button = group.querySelector('[data-admin-sidebar-group-toggle]');
        var panel = document.getElementById(button.getAttribute('aria-controls'));
        button.setAttribute('aria-expanded', String(open));
        if (panel) panel.hidden = !open;
    }

    groups.forEach(function (group) {
        var id = group.dataset.adminSidebarGroup;
        // The group holding the current page always starts open.
        if (!group.hasAttribute('data-has-active') && Object.prototype.hasOwnProperty.call(saved, id)) setOpen(group, !!saved[id]);
        group.querySelector('[data-admin-sidebar-group-toggle]').addEventListener('click', function () {
            // The layout's handler flips the state first; store the result.
            setTimeout(function () {
                saved[id] = group.querySelector('[data-admin-sidebar-group-toggle]').getAttribute('aria-expanded') === 'true';
                try { localStorage.setItem(KEY, JSON.stringify(saved)); } catch (e) {}
            }, 0);
        });
    });

    var input = document.querySelector('[data-admin-sidebar-search]');
    var noMatch = document.querySelector('[data-admin-sidebar-no-match]');
    if (!input) return;
    var before = null;
    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        if (q && before === null) before = groups.map(function (g) { return g.querySelector('[data-admin-sidebar-group-toggle]').getAttribute('aria-expanded') === 'true'; });
        var total = 0;
        groups.forEach(function (group, i) {
            var shown = 0;
            group.querySelectorAll('[data-admin-sidebar-item]').forEach(function (link) {
                var hit = !q || q.split(/\s+/).every(function (w) { return link.dataset.search.indexOf(w) !== -1; });
                link.hidden = !hit;
                if (hit) shown++;
            });
            group.hidden = q !== '' && shown === 0;
            if (q) setOpen(group, shown > 0); else if (before) setOpen(group, before[i]);
            total += shown;
        });
        if (!q) before = null;
        if (noMatch) noMatch.hidden = !q || total > 0;
    });
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            var first = document.querySelector('[data-admin-sidebar-item]:not([hidden])');
            if (first && input.value.trim()) { e.preventDefault(); window.location = first.href; }
        } else if (e.key === 'Escape') {
            input.value = ''; input.dispatchEvent(new Event('input'));
        }
    });
})();
</script>
