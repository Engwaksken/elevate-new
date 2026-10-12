<?php

namespace App\Support;

use App\Models\User;

class ParticipantFeedbackAccess
{
    public const ROLES = [
        'administrator', 'Administrator', 'admin', 'super-administrator', 'super-admin',
        'communications-lead', 'communication-lead', 'comms-lead',
        'programs-lead', 'program-lead', 'program-officer', 'programs-officer',
        'operations-lead', 'operations',
    ];

    public static function canView(?User $user): bool
    {
        return $user !== null
            && $user->isStaff()
            && ($user->isSuperAdmin() || $user->hasAnyRole(self::ROLES));
    }
}
