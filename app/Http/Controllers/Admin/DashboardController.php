<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Course;
use App\Models\Deliverable;
use App\Models\Enrolment;
use App\Models\IndicatorResult;
use App\Models\Programme;
use App\Models\PurchaseRequest;
use App\Models\Task;
use App\Models\User;
use App\Models\Workplan;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard.index', [
            'stats'=>[
                'participants'=>User::where('user_type','participant')->count(),
                'staff'=>User::where('user_type','staff')->count(),
                'programmes'=>Programme::where('status','active')->count(),
                'courses'=>Course::where('status','published')->count(),
                'enrolments'=>Enrolment::count(),
                'completed'=>Enrolment::where('status','completed')->count(),
                'open_tasks'=>Task::whereNotIn('status',['completed'])->count(),
                'overdue_tasks'=>Task::where('status','overdue')->count(),
                'open_deliverables'=>Deliverable::whereNotIn('status',['completed'])->count(),
                'approved_workplans'=>Workplan::where('status','approved')->count(),
                'pending_indicator_results'=>IndicatorResult::where('verification_status','submitted')->count(),
                'open_purchase_requests'=>PurchaseRequest::whereNotIn('status',['received','closed','cancelled','rejected'])->count(),
                'active_assets'=>Asset::whereNotIn('status',['disposed','lost','retired'])->count(),
            ],
            'recentTasks'=>Task::latest()->limit(6)->get(),
            'recentRequests'=>PurchaseRequest::latest()->limit(6)->get(),
        ]);
    }
}
