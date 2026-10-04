<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\User;

/**
 * Platform-level entry assessment that gates mentorship and job applications.
 * Configured by M&E on the platform settings screen.
 */
class AdmissionService
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function entryAssessmentId(): ?int
    {
        $id = (int) $this->settings->get('admissions.entry_assessment_id');

        return $id > 0 ? $id : null;
    }

    public function assessment(): ?Assessment
    {
        return $this->entryAssessmentId() ? Assessment::find($this->entryAssessmentId()) : null;
    }

    public function passed(?User $user): bool
    {
        $assessment = $this->assessment();

        if (! $assessment) {
            return true;
        }

        if (! $user) {
            return false;
        }

        $passMark = (float) ($assessment->pass_mark ?? 0);

        return AssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $user->id)
            ->where('status', 'graded')
            ->whereNotNull('percentage')
            ->where('percentage', '>=', $passMark)
            ->exists();
    }

    public function pending(?User $user): bool
    {
        return $this->entryAssessmentId() !== null && ! $this->passed($user);
    }

    public function requiredForMentorship(): bool
    {
        return $this->entryAssessmentId() !== null
            && (bool) $this->settings->get('admissions.mentorship_requires_assessment', false);
    }

    public function requiredForJobs(): bool
    {
        return $this->entryAssessmentId() !== null
            && (bool) $this->settings->get('admissions.jobs_require_assessment', false);
    }
}
