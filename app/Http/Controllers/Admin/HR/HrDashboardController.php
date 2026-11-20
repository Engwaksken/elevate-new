<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\StaffDocument;
use App\Models\HrPolicy;
use App\Models\LeaveRequest;
use App\Models\RecruitmentApplicant;
use App\Models\RecruitmentInterview;
use App\Models\RecruitmentVacancy;
use App\Services\HR\HrComplianceService;
use Illuminate\Support\Facades\Schema;

/** HR operations hub: recruitment, people, compliance and leave at a glance. */
class HrDashboardController extends Controller
{
    public function __invoke(HrComplianceService $compliance)
    {
        $summary = $compliance->summary();

        return view('admin.hr.dashboard', [
            'stats' => [
                'employees' => Employee::whereNotIn('status', ['exited'])->count(),
                'probation' => Employee::where('status', 'probation')->count(),
                'open_vacancies' => RecruitmentVacancy::where('status', 'open')->count(),
                'new_applicants' => RecruitmentApplicant::where('status', 'new')->count(),
                'shortlisted' => RecruitmentApplicant::whereIn('status', ['shortlisted', 'interview'])->count(),
                'policies' => HrPolicy::where('is_active', true)->count(),
                'staff_outstanding' => $summary->filter(fn ($r) => $r['documents'] + $r['policies'] > 0)->count(),
                'documents_to_review' => StaffDocument::where('status', 'submitted')->count(),
                'leave_pending' => Schema::hasTable('leave_requests') ? LeaveRequest::pending()->count() : 0,
            ],
            'upcomingInterviews' => RecruitmentInterview::with(['applicant', 'vacancy'])
                ->where('status', 'scheduled')->where('scheduled_at', '>=', now()->startOfDay())
                ->orderBy('scheduled_at')->limit(8)->get(),
            'vacancies' => RecruitmentVacancy::withCount('applicants')->where('status', 'open')->orderByRaw('closing_date IS NULL')->orderBy('closing_date')->limit(6)->get(),
            'outstanding' => $summary->filter(fn ($r) => $r['documents'] + $r['policies'] > 0)->sortByDesc(fn ($r) => $r['documents'] + $r['policies'])->take(8)->values(),
        ]);
    }
}
