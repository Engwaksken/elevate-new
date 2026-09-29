<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class StaffDashboardService
{
    public function for(User $user): array
    {
        return [
            'user' => $user,
            'abilities' => [
                'view_reports' => $this->allowsAny($user, ['reports.view', 'reports.export', 'view-reports']),
                'manage_courses' => $this->allowsAny($user, ['courses.view', 'courses.edit', 'manage-assigned-courses']),
                'manage_users' => $this->allowsAny($user, ['users.view', 'users.edit', 'manage-users']),
                'view_meal' => $this->allowsAny($user, ['meal.view', 'meal.manage']),
                'view_hr' => $this->allowsAny($user, ['hr.view', 'hr.manage', 'leave.view', 'appraisals.view']),
                'view_procurement' => $this->allowsAny($user, ['procurement.view', 'procurement.create', 'procurement.approve', 'procurement.receive']),
                'view_assets' => $this->allowsAny($user, ['assets.view', 'assets.manage', 'assets.dispose']),
            ],
            'stats' => [
                'participants' => $this->countWhere('users', ['user_type' => 'participant']),
                'staff' => $this->countWhere('users', ['user_type' => 'staff']),
                'published_courses' => $this->countWhere('courses', ['status' => 'published']),
                'active_enrolments' => $this->countWhereIn('enrolments', 'status', ['enrolled', 'in_progress']),
                'completed_learners' => $this->countWhere('enrolments', ['status' => 'completed']),
                'certificates' => $this->countTable('certificates'),

                'active_programmes' => $this->countWhere('programmes', ['status' => 'active']),
                'projects' => $this->countTable('projects'),
                'cohorts' => $this->countTable('cohorts'),
                'active_mentorships' => $this->countWhere('mentor_matches', ['status' => 'active']),
                'job_applications' => $this->countTable('job_applications'),
                'verified_outcomes' => $this->countWhere('participant_outcomes', ['verification_status' => 'verified']),
                'approved_workplans' => $this->countWhere('workplans', ['status' => 'approved']),
                'pending_indicator_results' => $this->countWhere('indicator_results', ['verification_status' => 'submitted']),

                'active_employees' => $this->countWhereIn('employees', 'status', ['active', 'probation', 'on_leave']),
                'pending_leave' => $this->countWhereIn('leave_requests', 'status', ['submitted', 'pending', 'supervisor_approved']),
                'active_assets' => $this->countWhereNotIn('assets', 'status', ['retired', 'disposed', 'lost']),
                'open_purchase_requests' => $this->countWhereNotIn('purchase_requests', 'status', ['closed', 'cancelled', 'rejected', 'received']),
                'branches' => $this->countTable('branches'),
                'events' => $this->countTable('events'),
                'notifications' => $this->countTable('notifications'),
            ],
            'quickLinks' => $this->quickLinks($user),
        ];
    }

    private function quickLinks(User $user): array
    {
        $links = [
            ['route' => 'admin.programmes.index', 'label' => 'Programmes', 'icon' => 'fa-diagram-project', 'permissions' => ['programmes.manage']],
            ['route' => 'admin.elearning.courses.index', 'label' => 'Courses', 'icon' => 'fa-graduation-cap', 'permissions' => ['courses.view', 'courses.edit']],
            ['route' => 'admin.mentorship.mentors.index', 'label' => 'Mentorship', 'icon' => 'fa-handshake', 'permissions' => ['mentors.view', 'mentors.manage', 'mentorship.match']],
            ['route' => 'admin.jobs.index', 'label' => 'Jobs', 'icon' => 'fa-briefcase', 'permissions' => ['jobs.manage']],
            ['route' => 'admin.hr.employees.index', 'label' => 'Human Resources', 'icon' => 'fa-users-gear', 'permissions' => ['hr.view', 'hr.manage']],
            ['route' => 'admin.procurement.requests.index', 'label' => 'Procurement', 'icon' => 'fa-cart-shopping', 'permissions' => ['procurement.view', 'procurement.create', 'procurement.approve', 'procurement.receive']],
            ['route' => 'admin.assets.index', 'label' => 'Assets', 'icon' => 'fa-laptop', 'permissions' => ['assets.view', 'assets.manage', 'assets.dispose']],
            ['route' => 'admin.settings.index', 'label' => 'Settings', 'icon' => 'fa-gear', 'permissions' => ['settings.manage']],
        ];

        return array_values(array_filter(
            $links,
            fn (array $link): bool => $this->allowsAny($user, $link['permissions'])
        ));
    }

    private function allowsAny(User $user, array $permissions): bool
    {
        try {
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }

            if (method_exists($user, 'hasAnyPermission')) {
                return $user->hasAnyPermission($permissions);
            }

            foreach ($permissions as $permission) {
                if ($user->can($permission)) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    private function countTable(string $table): int
    {
        return Schema::hasTable($table)
            ? (int) DB::table($table)->count()
            : 0;
    }

    private function countWhere(string $table, array $conditions): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);

        foreach ($conditions as $column => $value) {
            if (! Schema::hasColumn($table, $column)) {
                return $this->countTable($table);
            }

            $query->where($column, $value);
        }

        return (int) $query->count();
    }

    private function countWhereIn(string $table, string $column, array $values): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        if (! Schema::hasColumn($table, $column)) {
            return $this->countTable($table);
        }

        return (int) DB::table($table)->whereIn($column, $values)->count();
    }

    private function countWhereNotIn(string $table, string $column, array $values): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        if (! Schema::hasColumn($table, $column)) {
            return $this->countTable($table);
        }

        return (int) DB::table($table)->whereNotIn($column, $values)->count();
    }
}
