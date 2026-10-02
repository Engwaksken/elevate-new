<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;

class TimetableAccessService
{
    public function canManage(?User $user, Course $course): bool
    {
        return $user && $user->isStaff() && $user->isActive() && (
            $user->isSuperAdmin()
            || $user->hasAnyRole(['program-officer', 'programs-officer', 'programs-lead', 'program-lead', 'program-manager', 'operations', 'operations-lead', 'operations-officer'])
            || $user->hasPermission('timetable.manage')
            || ($user->hasAnyRole(['instructor', 'trainer']) && $user->instructedCourses()->whereKey($course->id)->exists())
        );
    }
}
