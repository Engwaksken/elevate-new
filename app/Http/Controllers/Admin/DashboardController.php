<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $count = function(string $table, ?callable $scope=null): int {
            if (!Schema::hasTable($table)) return 0;
            $q=DB::table($table);
            if ($scope) $scope($q);
            return (int)$q->count();
        };

        $stats=[
            'participants'=>$count('users',fn($q)=>$q->where('user_type','participant')),
            'staff'=>$count('users',fn($q)=>$q->where('user_type','staff')),
            'active_users'=>$count('users',fn($q)=>$q->whereIn('status',['active','approved'])),
            'programmes'=>$count('programmes'),
            'projects'=>$count('projects'),
            'cohorts'=>$count('cohorts'),
            'branches'=>$count('branches'),
            'courses'=>$count('courses'),
            'enrolments'=>$count('enrolments'),
            'completed_learning'=>$count('enrolments',fn($q)=>$q->where('status','completed')),
            'mentor_matches'=>$count('mentor_matches'),
            'mentorship_sessions'=>$count('mentorship_sessions'),
            'jobs'=>$count('jobs'),
            'job_applications'=>$count('job_applications'),
            'library_resources'=>$count('library_resources'),
            'workplans'=>$count('workplans'),
            'activities'=>$count('activities'),
            'indicators'=>$count('indicators'),
            'employees'=>$count('employees'),
            'assets'=>$count('assets'),
            'purchase_requests'=>$count('purchase_requests'),
        ];

        $recentUsers=Schema::hasTable('users')
            ? DB::table('users')->select('id','name','email','user_type','status','created_at')->latest('created_at')->limit(8)->get()
            : collect();

        $completionRate=$stats['enrolments']>0
            ? round(($stats['completed_learning']/$stats['enrolments'])*100,1)
            : 0;

        return view('admin.dashboard.index',compact('stats','recentUsers','completionRate'));
    }
}
