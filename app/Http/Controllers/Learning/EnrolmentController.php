<?php
namespace App\Http\Controllers\Learning;
use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Enrolment;
use App\Services\EnrolmentService;
class EnrolmentController extends Controller
{
    use ExportsTables;
    public function store(Course $course, EnrolmentService $enrolments)
    {
        abort_unless($course->status === 'published', 404);
        abort_unless($course->self_enrolment_enabled, 403);

        // A participant is not enrolled until she has passed the course's entry requirement.
        if ($course->entryRequirementPendingFor(auth()->id())) {
            return redirect()->route('learning.course.show', $course)
                ->with('error', 'Pass the entry assessment before you can enrol in this course.');
        }

        $enrolments->enrol(auth()->user(), $course);

        return redirect()->route('learning.my-courses')->with('success','You have been enrolled successfully.');
    }
    public function myCourses(Request $request)
    {
        $base=Enrolment::query()->where('user_id',auth()->id())->whereHas('course');
        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'My Learning',(clone $base)->with('course')->latest(),[
                'Course'=>'course.title',
                'Code'=>'course.code',
                'Status'=>fn($e)=>ucfirst(str_replace('_',' ',(string)$e->status)),
                'Progress (%)'=>fn($e)=>number_format((float)$e->progress_percent,1),
                'Final score'=>'final_score',
                'Enrolled'=>'enrolled_at',
                'Completed'=>'completed_at',
            ],[]);
        }
        $total=(clone $base)->count();
        $completed=(clone $base)->where(fn($q)=>$q->whereNotNull('completed_at')->orWhereIn('status',['completed','passed']))->count();
        $inProgress=(clone $base)->where(fn($q)=>$q->whereIn('status',['in_progress','started','active'])->orWhere(fn($n)=>$n->where('progress_percent','>',0)->where('progress_percent','<',100)))->count();
        $average=$total>0?round((float)((clone $base)->avg('progress_percent')??0),1):0;
        return view('learning.my-courses',[
            'enrolments'=>(clone $base)->with('course')->latest()->paginate(12),
            'stats'=>['enrolled'=>$total,'in_progress'=>$inProgress,'completed'=>$completed,'progress'=>$average],
        ]);
    }
}
