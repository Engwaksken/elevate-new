<?php
namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Admin\Concerns\BulkDeletesRecords;
use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\User;
use App\Services\EnrolmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrolmentAdminController extends Controller
{
    use ExportsTables;
    use BulkDeletesRecords;

    /** Matches the enrolments.status column. */
    private const STATUSES=['enrolled','in_progress','completed','withdrawn','failed'];

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

    public function store(Request $request, EnrolmentService $enrolments)
    {
        $data=$request->validate([
            'course_id'=>['required','exists:courses,id'],
            'user_id'=>['required','exists:users,id'],
            'cohort_id'=>['nullable','exists:cohorts,id'],
            'status'=>['nullable','in:'.implode(',',self::STATUSES)],
        ]);

        $cohortId=isset($data['cohort_id']) ? (int)$data['cohort_id'] : null;
        $status=$data['status'] ?? 'enrolled';
        $existing=Enrolment::where('course_id',$data['course_id'])->where('user_id',$data['user_id'])->first();

        if($existing){
            // Moving an existing enrolment must still respect one course per cohort.
            if(!in_array($status,EnrolmentService::INACTIVE_STATUSES,true)){
                $enrolments->assertCanEnrol((int)$data['user_id'],(int)$data['course_id'],$cohortId,$existing->id);
            }
            $existing->update(['cohort_id'=>$cohortId,'status'=>$status,'enrolled_at'=>now()]);

            return back()->with('success','Learner enrolment saved.');
        }

        // The service enforces one course per cohort and adds compulsory courses.
        $enrolments->enrol(
            User::findOrFail($data['user_id']),
            Course::findOrFail($data['course_id']),
            ['cohort_id'=>$cohortId,'status'=>$status,'enrolled_at'=>now()]
        );

        return back()->with('success','Learner enrolment saved. Any compulsory courses were added automatically.');
    }

    public function update(Request $request,Enrolment $enrolment,EnrolmentService $enrolments)
    {
        $data=$request->validate([
            'course_id'=>['nullable','exists:courses,id'],
            'cohort_id'=>['nullable','exists:cohorts,id'],
            'status'=>['required','in:'.implode(',',self::STATUSES)],
        ]);

        $cohortId=isset($data['cohort_id']) ? (int)$data['cohort_id'] : null;
        $courseId=(int)($data['course_id'] ?? $enrolment->course_id);

        if($courseId!==(int)$enrolment->course_id){
            return $this->reassign($enrolment,Course::findOrFail($courseId),$cohortId,$data['status'],$enrolments);
        }

        if(!in_array($data['status'],EnrolmentService::INACTIVE_STATUSES,true)){
            $enrolments->assertCanEnrol($enrolment->user_id,$enrolment->course_id,$cohortId,$enrolment->id,'cohort_id');
        }

        $enrolment->update(['cohort_id'=>$cohortId,'status'=>$data['status']]);

        return back()->with('success','Enrolment updated.');
    }

    /**
     * Move a participant to another course. Enrolment codes include the course,
     * so the old enrolment is kept as withdrawn (history and progress stay) and
     * the participant is enrolled in the new course, with its compulsory courses.
     */
    private function reassign(Enrolment $enrolment,Course $course,?int $cohortId,string $status,EnrolmentService $enrolments)
    {
        $from=$enrolment->course?->title ?? 'the previous course';

        if(!in_array($status,EnrolmentService::INACTIVE_STATUSES,true)){
            $enrolments->assertCanEnrol($enrolment->user_id,$course->id,$cohortId,$enrolment->id,'course_id');
        }

        DB::transaction(function() use($enrolment,$course,$cohortId,$status,$enrolments){
            $enrolment->update(['status'=>'withdrawn']);

            $existing=Enrolment::where('course_id',$course->id)->where('user_id',$enrolment->user_id)->first();

            if($existing){
                // An earlier enrolment in the target course is reopened rather than duplicated.
                $existing->update(['cohort_id'=>$cohortId,'status'=>$status,'enrolled_at'=>now()]);
                $enrolments->enrolCompulsory($existing->user,$course,$cohortId);
            }else{
                $enrolments->enrol($enrolment->user,$course,['cohort_id'=>$cohortId,'status'=>$status,'enrolled_at'=>now()]);
            }
        });

        return back()->with('success',"Participant moved from \"{$from}\" to \"{$course->title}\". The previous enrolment is kept as withdrawn.");
    }

    public function destroy(Enrolment $enrolment)
    {
        $enrolment->delete();

        return back()->with('success','Enrolment removed.');
    }
}
