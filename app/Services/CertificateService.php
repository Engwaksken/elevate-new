<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\User;
use Illuminate\Support\Str;

class CertificateService
{
    public function issueIfEligible(Course $course, User $user): ?Certificate
    {
        $enrolment = Enrolment::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $enrolment) {
            return null;
        }

        $eligible = (float) $enrolment->progress_percent >= 100
            && ($enrolment->final_score === null || (float) $enrolment->final_score >= (float) $course->pass_mark);

        if (! $eligible) {
            return null;
        }

        $enrolment->update([
            'status' => 'completed',
            'completed_at' => $enrolment->completed_at ?: now(),
        ]);

        return Certificate::firstOrCreate(
            ['course_id' => $course->id, 'user_id' => $user->id],
            [
                'certificate_number' => 'EH360-' . now()->format('Y') . '-' . strtoupper(Str::random(8)),
                'issued_on' => now()->toDateString(),
                'verification_token' => Str::uuid()->toString(),
            ]
        );
    }
}
