<?php
namespace App\Http\Controllers\Participant;
use App\Http\Controllers\Controller;
use App\Models\{CourseApplication,CourseApplicationAnswer,CourseCall};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CourseCallApplicationController extends Controller {
    public function index(){
        $calls=CourseCall::with(['course','cohort'])->where('status','published')
            ->where(fn($q)=>$q->whereNull('opens_at')->orWhere('opens_at','<=',now()))
            ->where(fn($q)=>$q->whereNull('closes_at')->orWhere('closes_at','>=',now()))
            ->latest()->paginate(12);
        $mine=CourseApplication::where('user_id',auth()->id())->with('courseCall.course')->get()->keyBy('course_call_id');
        return view('participant.course-calls.index',compact('calls','mine'));
    }
    public function show(CourseCall $courseCall){
        abort_unless($courseCall->isOpen(),404);
        $courseCall->load(['course','questions','entryAssessment']);
        $application=CourseApplication::firstOrCreate(['course_call_id'=>$courseCall->id,'user_id'=>auth()->id()]);
        $application->load('answers');
        return view('participant.course-calls.show',compact('courseCall','application'));
    }
    public function save(Request $request,CourseCall $courseCall){
        abort_unless($courseCall->isOpen(),422,'Applications are closed.');
        $application=CourseApplication::firstOrCreate(['course_call_id'=>$courseCall->id,'user_id'=>auth()->id()]);
        abort_if($application->submitted_at,422,'This application has already been submitted.');
        DB::transaction(function() use($request,$courseCall,$application){
            foreach($courseCall->questions as $question){
                $key='question_'.$question->id;
                if($question->is_required) $request->validate([$key=>'required']);
                $value=$request->input($key);
                CourseApplicationAnswer::updateOrCreate(
                    ['course_application_id'=>$application->id,'course_call_question_id'=>$question->id],
                    ['answer_text'=>is_array($value)?null:$value,'answer_json'=>is_array($value)?$value:null]
                );
            }
            if($request->boolean('submit')){
                $application->update(['status'=>'submitted','submitted_at'=>now()]);
            }
        });
        return back()->with('success',$request->boolean('submit')?'Application submitted.':'Application draft saved.');
    }
}