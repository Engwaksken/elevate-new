<?php

namespace App\Support;

use App\Models\User;

/**
 * Who may manage the shared pick-lists:
 *  - Departments: HR (role or hr.manage) and administrators.
 *  - Funding sources: procurement (procurement.create / procurement.approve),
 *    finance and procurement roles, and administrators.
 */
final class MasterListAccess
{
    public const DEPARTMENT_ROLES = ['hr', 'HR'];

    public const FUNDING_ROLES = ['finance', 'Finance', 'procurement-officer', 'Procurement Officer'];

    public static function canManageDepartments(?User $user): bool
    {
        return $user !== null && $user->isActive() && (
            $user->isSuperAdmin()
            || $user->hasPermission('hr.manage')
            || $user->hasAnyRole(self::DEPARTMENT_ROLES)
        );
    }

    public static function canManageFundingSources(?User $user): bool
    {
        return $user !== null && $user->isActive() && (
            $user->isSuperAdmin()
            || $user->hasAnyPermission(['procurement.create', 'procurement.approve'])
            || $user->hasAnyRole(self::FUNDING_ROLES)
        );
    }

    /** Link to the departments page for people who may manage it (for form hints). */
    public static function departmentsUrl(?User $user): ?string
    {
        return self::canManageDepartments($user) ? route('admin.departments.index') : null;
    }

    public static function fundingSourcesUrl(?User $user): ?string
    {
        return self::canManageFundingSources($user) ? route('admin.funding-sources.index') : null;
    }
}
