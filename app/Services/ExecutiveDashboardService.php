<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Employee;
use App\Models\Enrolment;
use App\Models\IndicatorResult;
use App\Models\JobApplication;
use App\Models\LeaveRequest;
use App\Models\MentorMatch;
use App\Models\ParticipantOutcome;
use App\Models\Programme;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Workplan;

class ExecutiveDashboardService
{
    public function summary(): array
    {
        return [
            'participants'=>User::where('user_type','participant')->count(),
            'staff'=>User::where('user_type','staff')->count(),
            'active_programmes'=>Programme::where('status','active')->count(),
            'published_courses'=>Course::where('status','published')->count(),

            'active_enrolments'=>Enrolment::whereIn('status',['enrolled','in_progress'])->count(),
            'completed_learners'=>Enrolment::where('status','completed')->count(),
            'certificates'=>Certificate::count(),
            'active_mentorships'=>MentorMatch::where('status','active')->count(),

            'job_applications'=>JobApplication::count(),
            'verified_outcomes'=>ParticipantOutcome::where('verification_status','verified')->count(),
            'approved_workplans'=>Workplan::where('status','approved')->count(),
            'pending_indicator_results'=>IndicatorResult::where('verification_status','submitted')->count(),

            'active_employees'=>Employee::whereIn('status',['active','probation','on_leave'])->count(),
            'pending_leave'=>LeaveRequest::whereIn('status',['submitted','pending','supervisor_approved'])->count(),
            'active_assets'=>Asset::whereNotIn('status',['retired','disposed','lost'])->count(),
            'open_purchase_requests'=>PurchaseRequest::whereNotIn('status',['closed','cancelled','rejected','received'])->count(),
        ];
    }
}
