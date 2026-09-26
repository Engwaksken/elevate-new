<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\Appraisal;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetDisposal;
use App\Models\AssetMaintenance;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Deliverable;
use App\Models\Employee;
use App\Models\Enrolment;
use App\Models\Indicator;
use App\Models\IndicatorResult;
use App\Models\LeaveRequest;
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
use App\Observers\OperationalNotificationObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $auditedModels=[
            Activity::class,
            Appraisal::class,
            Asset::class,
            AssetAssignment::class,
            AssetDisposal::class,
            AssetMaintenance::class,
            Certificate::class,
            Course::class,
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
            Appraisal::class,
            Certificate::class,
            Deliverable::class,
            LeaveRequest::class,
            PurchaseRequest::class,
            Task::class,
            Workplan::class,
        ];

        foreach($notificationModels as $model){
            if (class_exists($model)) {
                $model::observe(OperationalNotificationObserver::class);
            }
        }
    }
}
