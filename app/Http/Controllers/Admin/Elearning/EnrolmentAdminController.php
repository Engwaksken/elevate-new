<?php
namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\User;
use Illuminate\Http\Request;

class EnrolmentAdminController extends Controller
{
    public function index(Request $request)
    {
        $query=Enrolment::with(['course','user','cohort'])->latest();

        if($search=trim((string)$request->get('search'))){
            $query->whereHas('user',fn($q)=>$q->where('name','like',"%{$search}%")
                ->orWhere('email','like',"%{$search}%"));
        }

        if($courseId=$request->get('course_id')) $query->where('course_id',$courseId);
        if($cohortId=$request->get('cohort_id')) $query->where('cohort_id',$cohortId);
        if($status=$request->get('status')) $query->where('status',$status);

        return view('admin.elearning.enrolments.index',[
            'enrolments'=>$query->paginate(25)->withQueryString(),
            'courses'=>Course::orderBy('title')->get(),
            'cohorts'=>Cohort::orderBy('name')->get(),
            'learners'=>User::where('user_type','participant')->orderBy('name')->get(),
            'stats'=>[
                'total'=>Enrolment::count(),
                'active'=>Enrolment::whereIn('status',['enrolled','active','in_progress'])->count(),
                'completed'=>Enrolment::where('status','completed')->count(),
                'withdrawn'=>Enrolment::whereIn('status',['withdrawn','cancelled'])->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data=$request->validate([
            'course_id'=>['required','exists:courses,id'],
            'user_id'=>['required','exists:users,id'],
            'cohort_id'=>['nullable','exists:cohorts,id'],
            'status'=>['nullable','in:enrolled,active,in_progress,completed,withdrawn,cancelled'],
        ]);

        Enrolment::updateOrCreate(
            ['course_id'=>$data['course_id'],'user_id'=>$data['user_id']],
            [
                'cohort_id'=>$data['cohort_id'] ?? null,
                'status'=>$data['status'] ?? 'enrolled',
                'enrolled_at'=>now(),
            ]
        );

        return back()->with('success','Learner enrolment saved.');
    }

    public function update(Request $request,Enrolment $enrolment)
    {
        $data=$request->validate([
            'cohort_id'=>['nullable','exists:cohorts,id'],
            'status'=>['required','in:enrolled,active,in_progress,completed,withdrawn,cancelled'],
        ]);

        $enrolment->update($data);

        return back()->with('success','Enrolment updated.');
    }

    public function destroy(Enrolment $enrolment)
    {
        $enrolment->delete();

        return back()->with('success','Enrolment removed.');
    }
}
