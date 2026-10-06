<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Presentation config for the three public-facing user types
 * (participant, mentor, employer): labels, entry routes and the
 * sidebar navigation each one sees after signing in.
 *
 * Staff never get a role shell; they keep layouts/admin.
 * Every route is checked with Route::has() so a missing route is
 * simply omitted instead of throwing.
 */
class RoleShell
{
    public const ROLES = [
        'participant' => [
            'label' => 'Participant',
            'icon' => 'fa-graduation-cap',
            'tagline' => 'Learn new skills, get mentored and move into work.',
            'home' => 'dashboard',
            'login' => 'login',
            'join' => 'register',
        ],
        'mentor' => [
            'label' => 'Mentor',
            'icon' => 'fa-handshake-angle',
            'tagline' => 'Guide women in tech through goals, sessions and milestones.',
            'home' => 'mentorship.dashboard',
            'login' => 'partners.mentor.login',
            'join' => 'public.partners.mentor',
        ],
        'employer' => [
            'label' => 'Employer',
            'icon' => 'fa-building',
            'tagline' => 'Post opportunities and hire skilled, job-ready talent.',
            'home' => 'employer.jobs.index',
            'login' => 'partners.employer.login',
            'join' => 'public.partners.employer',
        ],
    ];

    /** The role shell for a user, or null for guests and staff. */
    public static function roleFor(?User $user): ?string
    {
        if (! $user || $user->isStaff()) {
            return null;
        }

        return array_key_exists($user->user_type, self::ROLES) ? $user->user_type : null;
    }

    public static function meta(string $role): array
    {
        return self::ROLES[$role] ?? self::ROLES['participant'];
    }

    /** Resolve a named route if it exists. */
    public static function url(?string $route, string $fragment = ''): ?string
    {
        return $route && Route::has($route) ? route($route).$fragment : null;
    }

    /** Where a signed-in user should land. */
    public static function homeUrl(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        if ($user->isStaff()) {
            return self::url('admin.dashboard');
        }

        $role = self::roleFor($user);

        return $role ? self::url(self::meta($role)['home']) : null;
    }

    /**
     * Sidebar groups for a role: [group label => [item, ...]].
     * Item: route, label, icon, active (route patterns), fragment.
     */
    public static function navigation(string $role): array
    {
        $groups = match ($role) {
            'mentor' => [
                'Overview' => [
                    ['route' => 'mentorship.dashboard', 'label' => 'Mentor Dashboard', 'icon' => 'fa-gauge-high', 'active' => ['mentorship.dashboard']],
                    ['route' => 'calendar.index', 'label' => 'Calendar', 'icon' => 'fa-calendar-days', 'active' => ['calendar.*']],
                    ['route' => 'notifications.index', 'label' => 'Notifications', 'icon' => 'fa-bell', 'active' => ['notifications.*']],
                ],
                'Mentoring' => [
                    ['route' => 'mentorship.dashboard', 'fragment' => '#mentee-goals', 'label' => 'Mentee Goals', 'icon' => 'fa-flag', 'active' => []],
                    ['route' => 'mentorship.mentor-profile.edit', 'label' => 'Mentor Profile', 'icon' => 'fa-id-badge', 'active' => ['mentorship.mentor-profile.*']],
                ],
                'Explore' => [
                    ['route' => 'events.index', 'label' => 'Events', 'icon' => 'fa-calendar-check', 'active' => ['events.index', 'events.show']],
                    ['route' => 'library.index', 'label' => 'Library', 'icon' => 'fa-book-open', 'active' => ['library.*']],
                ],
                'Account' => [
                    ['route' => 'profile.edit', 'label' => 'Account Settings', 'icon' => 'fa-user-gear', 'active' => ['profile.*']],
                ],
            ],
            'employer' => [
                'Overview' => [
                    ['route' => 'employer.jobs.index', 'label' => 'Job Postings', 'icon' => 'fa-briefcase', 'active' => ['employer.jobs.*']],
                    ['route' => 'calendar.index', 'label' => 'Calendar', 'icon' => 'fa-calendar-days', 'active' => ['calendar.*']],
                    ['route' => 'notifications.index', 'label' => 'Notifications', 'icon' => 'fa-bell', 'active' => ['notifications.*']],
                ],
                'Recruitment' => [
                    ['route' => 'employer.applicants.index', 'label' => 'Applicants', 'icon' => 'fa-users', 'active' => ['employer.applicants.*']],
                    ['route' => 'jobs.index', 'label' => 'Public Job Board', 'icon' => 'fa-globe', 'active' => ['jobs.index', 'jobs.show']],
                ],
                'Account' => [
                    ['route' => 'employer.profile.edit', 'label' => 'Company Profile', 'icon' => 'fa-building', 'active' => ['employer.profile.*']],
                    ['route' => 'profile.edit', 'label' => 'Account Settings', 'icon' => 'fa-user-gear', 'active' => ['profile.*']],
                ],
            ],
            default => [
                'Overview' => [
                    ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'active' => ['dashboard']],
                    ['route' => 'calendar.index', 'label' => 'Calendar', 'icon' => 'fa-calendar-days', 'active' => ['calendar.*']],
                    ['route' => 'certificates.mine', 'label' => 'My Certificates', 'icon' => 'fa-award', 'active' => ['certificates.mine']],
                    ['route' => 'notifications.index', 'label' => 'Notifications', 'icon' => 'fa-bell', 'active' => ['notifications.*']],
                ],
                'Growth' => [
                    ['route' => 'learning.my-courses', 'label' => 'My Learning', 'icon' => 'fa-graduation-cap', 'active' => ['learning.my-courses', 'learning.course.dashboard', 'learning.lesson.*', 'learning.assessment.*']],
                    ['route' => 'learning.index', 'requires' => 'learning.my-courses', 'label' => 'Browse Courses', 'icon' => 'fa-magnifying-glass', 'active' => ['learning.index', 'learning.course.show']],
                    ['route' => 'mentorship.dashboard', 'label' => 'Mentorship', 'icon' => 'fa-user-group', 'active' => ['mentorship.*']],
                    ['route' => 'mentorship.dashboard', 'fragment' => '#goals', 'label' => 'Goals', 'icon' => 'fa-flag', 'active' => []],
                    ['route' => 'career.resume.index', 'label' => 'Resume Builder', 'icon' => 'fa-file-lines', 'active' => ['career.*']],
                ],
                'Opportunities' => [
                    ['route' => 'participant.course-calls.index', 'label' => 'Course Opportunities', 'icon' => 'fa-bullhorn', 'active' => ['participant.course-calls.*']],
                    ['route' => 'jobs.index', 'label' => 'Jobs', 'icon' => 'fa-briefcase', 'active' => ['jobs.index', 'jobs.show', 'jobs.recommendations', 'jobs.saved']],
                    ['route' => 'jobs.applications', 'label' => 'My Applications', 'icon' => 'fa-folder-open', 'active' => ['jobs.applications', 'jobs.applications.*']],
                    ['route' => 'library.index', 'label' => 'Library', 'icon' => 'fa-book-open', 'active' => ['library.*']],
                ],
                'Account' => [
                    ['route' => 'participant.help', 'label' => 'Help & Support', 'icon' => 'fa-circle-question', 'active' => ['participant.help']],
                    ['route' => 'profile.edit', 'label' => 'Profile', 'icon' => 'fa-user', 'active' => ['profile.*']],
                ],
            ],
        };

        $resolved = [];
        foreach ($groups as $label => $items) {
            $items = array_values(array_filter($items, function (array $item) {
                return Route::has($item['route']) && (! isset($item['requires']) || Route::has($item['requires']));
            }));

            if ($items !== []) {
                $resolved[$label] = array_map(fn (array $item) => $item + [
                    'url' => route($item['route']).($item['fragment'] ?? ''),
                    'is_active' => $item['active'] !== [] && request()->routeIs(...$item['active']),
                ], $items);
            }
        }

        return $resolved;
    }
}
