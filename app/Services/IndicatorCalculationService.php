<?php
namespace App\Services;

use App\Models\Indicator;
use App\Models\User;
use App\Models\Enrolment;
use App\Models\Certificate;
use App\Models\MentorMatch;
use App\Models\JobApplication;
use App\Models\ParticipantOutcome;

class IndicatorCalculationService
{
    public function calculate(Indicator $indicator): ?float
    {
        return match($indicator->calculation_key) {
            'registered_participants' => (float) User::where('user_type','participant')->count(),
            'learners_enrolled' => (float) Enrolment::count(),
            'learners_completed' => (float) Enrolment::where('status','completed')->count(),
            'certified_learners' => (float) Certificate::count(),
            'active_mentorship_matches' => (float) MentorMatch::where('status','active')->count(),
            'job_applications' => (float) JobApplication::count(),
            'verified_employment_outcomes' => (float) ParticipantOutcome::where('verification_status','verified')->count(),
            default => null,
        };
    }
}
