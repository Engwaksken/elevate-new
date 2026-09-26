<?php
namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class CourseAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query=Course::withCount(['instructors','cohorts'])->with(['instructors','cohorts']);

        if($search=trim((string)$request->get('search'))){
            $query->where(fn($q)=>$q->where('title','like',"%{$search}%")
                ->orWhere('code','like',"%{$search}%"));
        }

        return view('admin.elearning.assignments.index',[
            'courses'=>$query->orderBy('title')->paginate(20)->withQueryString(),
            'stats'=>[
                'courses'=>Course::count(),
                'assigned'=>Course::whereHas('instructors')->count(),
                'unassigned'=>Course::whereDoesntHave('instructors')->count(),
                'cohort_linked'=>Course::whereHas('cohorts')->count(),
            ],
        ]);
    }

    public function edit(Course $course)
    {
        $roleId=Role::where('slug','instructor')->value('id');

        return view('admin.elearning.assignments.edit',[
            'course'=>$course->load(['instructors','cohorts']),
            'instructors'=>User::where('user_type','staff')
                ->when($roleId,fn($q)=>$q->whereHas('roles',fn($r)=>$r->where('roles.id',$roleId)))
                ->orderBy('name')->get(),
            'cohorts'=>Cohort::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request,Course $course)
    {
        $data=$request->validate([
            'instructors'=>['nullable','array'],
            'instructors.*'=>['integer','exists:users,id'],
            'lead_instructor_id'=>['nullable','integer','exists:users,id'],
            'cohorts'=>['nullable','array'],
            'cohorts.*'=>['integer','exists:cohorts,id'],
        ]);

        $sync=[];
        foreach($data['instructors'] ?? [] as $userId){
            $sync[$userId]=['is_lead'=>(int)$userId===(int)($data['lead_instructor_id'] ?? 0)];
        }

        $course->instructors()->sync($sync);
        $course->cohorts()->sync($data['cohorts'] ?? []);

        return redirect()->route('admin.elearning.assignments.index')->with('success','Course assignments updated.');
    }
}
