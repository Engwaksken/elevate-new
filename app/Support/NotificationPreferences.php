<?php

namespace App\Support;

use App\Models\User;

/**
 * Maps notification type slugs to the user-facing categories shown on the
 * profile preferences screen, and decides whether a user wants a type.
 */
class NotificationPreferences
{
    /**
     * category => list of type prefixes.
     */
    public const CATEGORIES = [
        'learning' => [
            'enrolment', 'lesson', 'assignment', 'course_', 'assessment', 'certificate_',
        ],
        'mentorship' => [
            'mentorship', 'mentor', 'goal_review',
        ],
        'appointments' => [
            'appointment',
        ],
        'jobs' => [
            'job_application', 'jobs',
        ],
        'account' => [
            'account',
        ],
        'reminders' => [
            'course_timetable_reminder', 'reminder',
        ],
    ];

    public static function labels(): array
    {
        return [
            'learning' => 'Learning, courses and assessments',
            'mentorship' => 'Mentorship and goals',
            'appointments' => 'Instructor appointments',
            'jobs' => 'Jobs and opportunities',
            'account' => 'Account and roles',
            'reminders' => 'Timetable reminders',
        ];
    }

    public static function categoryFor(string $type): string
    {
        foreach (self::CATEGORIES as $category => $prefixes) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($type, $prefix)) {
                    return $category;
                }
            }
        }

        return 'other';
    }

    public static function allows(User $user, string $type): bool
    {
        $preferences = $user->notification_preferences;

        if (! is_array($preferences)) {
            return true;
        }

        $disabled = $preferences['disabled'] ?? [];

        return ! in_array(self::categoryFor($type), $disabled, true);
    }
}
