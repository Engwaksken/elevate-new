<?php
namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Admin\Concerns\BulkDeletesRecords;
use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\User;
use Illuminate\Http\Request;

class EnrolmentAdminController extends Controller
{
    use ExportsTables;
    use BulkDeletesRecords;

    protected function bulkDeleteModel(): string
    {
        return Enrolment::class;
    }

    public function index(Request $request)
    {
        $query=Enrolment::with(['course','user','cohort'])->latest();

        if($search=trim((string)$request->get('search'))){
            $query->where(fn ($q) => $q->where('enrolment_code', 'like', "%{$search}%")
                ->orWhereHas('user',fn($user)=>$user->where('name','like',"%{$search}%")
                    ->orWhere('participant_code','like',"%{$search}%")
                    ->orWhere('email','like',"%{$search}%")));
        }

        if($courseId=$request->get('course_id')) $query->where('course_id',$courseId);
        if($cohortId=$request->get('cohort_id')) $query->where('cohort_id',$cohortId);
        if($status=$request->get('status')) $query->where('status',$status);

        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'Enrolments',$query,[
                'Learner'=>'user.name',
                'Email'=>'user.email',
                'Participant code'=>'user.participant_code',
                'Enrolment code'=>'enrolment_code',
                'Course'=>'course.title',
                'Cohort'=>'cohort.name',
                'Status'=>'status',
                'Progress (%)'=>'progress_percent',
                'Enrolled'=>'enrolled_at',
                'Completed'=>'completed_at',
            ],null,['course_id'=>'Course','cohort_id'=>'Cohort']);
        }

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
