<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Who may work on leave requests:
 *  - HR and administrators: every request; final approval, reject, edit,
 *    cancel and delete.
 *  - Supervisors: requests from the employees they supervise; supervisor
 *    approval and rejection while the request awaits them.
 *  - Everyone else: no access (staff use My Leave for their own requests).
 */
final class LeaveApprovalAccess
{
    /** Role slugs (or names) that count as HR / administration. */
    public const HR_ROLES = ['hr', 'HR', 'administrator', 'Administrator', 'super-administrator', 'super-admin'];

    /** Statuses waiting for the supervisor ("pending" and "submitted" are both used). */
    public const AWAITING_SUPERVISOR = ['pending', 'submitted'];

    public static function isHr(?User $user): bool
    {
        return $user !== null && $user->isActive()
            && ($user->isSuperAdmin() || $user->hasAnyRole(self::HR_ROLES));
    }

    /** @return array<int> ids of employees this user supervises */
    public static function supervisedEmployeeIds(?User $user): array
    {
        if (! $user) {
            return [];
        }

        // Cached per user object (one request), never across requests or tests.
        static $cache = null;
        $cache ??= new \WeakMap();

        return $cache[$user] ??= Employee::where('supervisor_user_id', $user->id)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public static function isSupervisor(?User $user): bool
    {
        return self::supervisedEmployeeIds($user) !== [];
    }

    public static function canAccess(?User $user): bool
    {
        return self::isHr($user) || ($user?->isActive() && self::isSupervisor($user));
    }

    /** Limit a leave-request query to what this user may see. */
    public static function scope(Builder $query, User $user): Builder
    {
        return self::isHr($user) ? $query : $query->whereIn('employee_id', self::supervisedEmployeeIds($user));
    }

    public static function supervises(User $user, LeaveRequest $leave): bool
    {
        return in_array((int) $leave->employee_id, self::supervisedEmployeeIds($user), true);
    }

    public static function canSupervisorApprove(User $user, LeaveRequest $leave): bool
    {
        return in_array($leave->status, self::AWAITING_SUPERVISOR, true)
            && (self::supervises($user, $leave) || self::isHr($user));
    }

    /** HR gives final approval after the supervisor, or directly when the employee has no supervisor. */
    public static function canHrApprove(User $user, LeaveRequest $leave): bool
    {
        if (! self::isHr($user)) {
            return false;
        }

        return $leave->status === 'supervisor_approved'
            || (in_array($leave->status, self::AWAITING_SUPERVISOR, true) && ! $leave->employee?->supervisor_user_id);
    }

    public static function canReject(User $user, LeaveRequest $leave): bool
    {
        if (self::isHr($user)) {
            return in_array($leave->status, [...self::AWAITING_SUPERVISOR, 'supervisor_approved'], true);
        }

        return in_array($leave->status, self::AWAITING_SUPERVISOR, true) && self::supervises($user, $leave);
    }

    /** Dates, type and notes can be corrected until final approval. */
    public static function canEdit(User $user, LeaveRequest $leave): bool
    {
        return self::isHr($user) && in_array($leave->status, [...self::AWAITING_SUPERVISOR, 'supervisor_approved'], true);
    }

    /** An approved request can be cancelled by HR (days go back to the balance). */
    public static function canCancel(User $user, LeaveRequest $leave): bool
    {
        return self::isHr($user) && $leave->status === 'hr_approved';
    }
}
