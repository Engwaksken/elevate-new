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
        $user = request()->user();

        abort_unless($user && $user->isStaff() && $user->isActive(), 403);

        $can = static function (string|array $permissions) use ($user): bool {
            return $user->isSuperAdmin()
                || $user->hasAnyPermission((array) $permissions);
        };

        $stats = [];

        if ($can(['users.view', 'users.edit'])) {
            $stats['participants'] = User::where('user_type', 'participant')->count();
            $stats['staff'] = User::where('user_type', 'staff')->count();
        }

        if ($can(['programmes.view', 'programmes.manage'])) {
            $stats['programmes'] = Programme::where('status', 'active')->count();
        }

        if ($can(['courses.view', 'courses.edit'])) {
            $stats['courses'] = Course::where('status', 'published')->count();
        }

        if ($can(['students.view', 'students.edit'])) {
            $stats['enrolments'] = Enrolment::count();
            $stats['completed'] = Enrolment::where('status', 'completed')->count();
        }

        if ($can('tasks.manage')) {
            $stats['open_tasks'] = Task::whereNotIn('status', ['completed'])->count();
            $stats['overdue_tasks'] = Task::where('status', 'overdue')->count();
            $stats['open_deliverables'] = Deliverable::whereNotIn('status', ['completed'])->count();
        }

        if ($can(['workplans.view', 'workplans.edit', 'workplans.approve'])) {
            $stats['approved_workplans'] = Workplan::where('status', 'approved')->count();
        }

        if ($can(['indicators.view', 'indicators.manage', 'indicators.verify', 'meal.view', 'meal.manage'])) {
            $stats['pending_indicator_results'] = IndicatorResult::where('verification_status', 'submitted')->count();
        }

        if ($can(['procurement.view', 'procurement.create', 'procurement.approve', 'procurement.receive'])) {
            $stats['open_purchase_requests'] = PurchaseRequest::whereNotIn(
                'status',
                ['received', 'closed', 'cancelled', 'rejected']
            )->count();
        }

        if ($can(['assets.view', 'assets.manage', 'assets.dispose'])) {
            $stats['active_assets'] = Asset::whereNotIn('status', ['disposed', 'lost', 'retired'])->count();
        }

        return view('admin.dashboard.index', [
            'stats' => $stats,
            'recentTasks' => $can('tasks.manage')
                ? Task::latest()->limit(6)->get()
                : collect(),
            'recentRequests' => $can(['procurement.view', 'procurement.create', 'procurement.approve', 'procurement.receive'])
                ? PurchaseRequest::latest()->limit(6)->get()
                : collect(),
            'dashboardAccess' => [
                'tasks' => $can('tasks.manage'),
                'procurement' => $can(['procurement.view', 'procurement.create', 'procurement.approve', 'procurement.receive']),
            ],
        ]);
    }
}
