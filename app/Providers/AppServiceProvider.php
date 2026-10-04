<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Appraisal;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetDisposal;
use App\Models\AssetMaintenance;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseAnnouncement;
use App\Models\CourseApplication;
use App\Models\Deliverable;
use App\Models\Employee;
use App\Models\Enrolment;
use App\Models\Indicator;
use App\Models\JobApplication;
use App\Models\IndicatorResult;
use App\Models\LeaveRequest;
use App\Models\Lesson;
use App\Models\MentorMatch;
use App\Models\MentorshipSession;
use App\Models\Milestone;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Result;
use App\Models\ResultsFramework;
use App\Models\StaffExit;
use App\Models\Supplier;
use App\Models\Task;
use App\Models\Workplan;
use App\Observers\AuditableObserver;
use App\Observers\HrNotificationObserver;
use App\Observers\LearningNotificationObserver;
use App\Observers\OperationalNotificationObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Super administrators may perform every ability checked through Gates/Policies.
        Gate::before(fn ($user) => method_exists($user,'isSuperAdmin') && $user->isSuperAdmin() ? true : null);

        $auditedModels=[
            Activity::class,
            Appraisal::class,
            Asset::class,
            AssetAssignment::class,
            AssetDisposal::class,
            AssetMaintenance::class,
            Certificate::class,
            Course::class,
            \App\Models\CmsPage::class,
            \App\Models\CourseTimeSlot::class,
            Deliverable::class,
            Employee::class,
            Enrolment::class,
            Indicator::class,
            IndicatorResult::class,
            LeaveRequest::class,
            Milestone::class,
            PurchaseOrder::class,
            PurchaseRequest::class,
            Result::class,
            ResultsFramework::class,
            StaffExit::class,
            Supplier::class,
            Task::class,
            Workplan::class,
        ];

        foreach($auditedModels as $model){
            if (class_exists($model)) {
                $model::observe(AuditableObserver::class);
            }
        }

        $notificationModels=[
            Activity::class,
            Certificate::class,
            Deliverable::class,
            Milestone::class,
            PurchaseRequest::class,
            Task::class,
            Workplan::class,
        ];

        foreach($notificationModels as $model){
            if (class_exists($model)) {
                $model::observe(OperationalNotificationObserver::class);
            }
        }

        // Leave and appraisal notices are routed to supervisor / HR / employee by status.
        Appraisal::observe(HrNotificationObserver::class);
        LeaveRequest::observe(HrNotificationObserver::class);

        $learningNotificationModels=[
            Enrolment::class,
            Lesson::class,
            Assessment::class,
            AssessmentAttempt::class,
            CourseAnnouncement::class,
            CourseApplication::class,
            MentorMatch::class,
            MentorshipSession::class,
            JobApplication::class,
        ];

        foreach($learningNotificationModels as $model){
            $model::observe(LearningNotificationObserver::class);
        }

        // Programme/project code changes re-sync participant ID prefixes.
        foreach ([\App\Models\Programme::class, \App\Models\Project::class] as $model) {
            if (class_exists($model)) {
                $model::observe(\App\Observers\IdentityResyncObserver::class);
            }
        }
    }
}
