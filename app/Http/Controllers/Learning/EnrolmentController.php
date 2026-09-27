<?php
namespace App\Http\Controllers\Learning;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrolment;
class EnrolmentController extends Controller
{
    public function store(Course $course)
    {
        abort_unless($course->status === 'published', 404);
        abort_unless($course->self_enrolment_enabled, 403);
        Enrolment::firstOrCreate(['course_id'=>$course->id,'user_id'=>auth()->id()],['status'=>'enrolled','enrolled_at'=>now()]);
        return redirect()->route('learning.my-courses')->with('success','You have been enrolled successfully.');
    }
    public function myCourses()
    {
        $base=Enrolment::query()->where('user_id',auth()->id());
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