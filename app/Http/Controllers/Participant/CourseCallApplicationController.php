<?php
namespace App\Http\Controllers\Participant;
use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\CourseApplication;
use App\Models\CourseApplicationAnswer;
use App\Models\CourseCall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CourseCallApplicationController extends Controller
{
    use ExportsTables;

    public function index(Request $request){ $userId=auth()->id(); $open=CourseCall::with(['course','cohort'])->where('status','published')->where(fn($q)=>$q->whereNull('opens_at')->orWhere('opens_at','<=',now()))->where(fn($q)=>$q->whereNull('closes_at')->orWhere('closes_at','>=',now())); $apps=CourseApplication::query()->where('user_id',$userId);
        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'My Course Applications',(clone $apps)->with('courseCall.course')->latest(),[
                'Opportunity'=>'courseCall.title',
                'Course'=>'courseCall.course.title',
                'Status'=>fn($a)=>ucwords(str_replace('_',' ',(string)$a->status)),
                'Submitted'=>'submitted_at',
                'Reviewed'=>'reviewed_at',
                'Enrolled'=>'enrolled_at',
                'Started'=>'created_at',
            ],[]);
        }
        return view('participant.course-calls.index',['calls'=>(clone $open)->latest()->paginate(12,['*'],'calls_page'),'submissions'=>(clone $apps)->with('courseCall.course')->latest()->paginate(12,['*'],'submissions_page'),'mine'=>(clone $apps)->get()->keyBy('course_call_id'),'stats'=>['available'=>(clone $open)->count(),'applications'=>(clone $apps)->count(),'review'=>(clone $apps)->whereIn('status',['submitted','under_review','reviewing'])->count(),'successful'=>(clone $apps)->whereIn('status',['accepted','approved','successful','enrolled'])->count()]]); }
    public function show(CourseCall $courseCall){ $application=CourseApplication::where('course_call_id',$courseCall->id)->where('user_id',auth()->id())->with('answers')->first(); if(!$courseCall->isOpen()&&!$application) abort(404); $courseCall->load(['course','questions','entryAssessment']); if(!$application){$application=new CourseApplication(['course_call_id'=>$courseCall->id,'user_id'=>auth()->id(),'status'=>'draft']);$application->setRelation('answers',collect());} return view('participant.course-calls.show',compact('courseCall','application')); }
    public function save(Request $request,CourseCall $courseCall){ abort_unless($courseCall->isOpen(),422,'Applications are closed.'); $application=CourseApplication::firstOrCreate(['course_call_id'=>$courseCall->id,'user_id'=>auth()->id()],['status'=>'draft']); $revision=['returned_for_revision','revision_requested']; abort_if($application->submitted_at&&!in_array($application->status,$revision,true),422,'This application has already been submitted.'); DB::transaction(function()use($request,$courseCall,$application,$revision){foreach($courseCall->questions as $question){$key='question_'.$question->id;if($question->is_required&&$request->boolean('submit'))$request->validate([$key=>'required']);$value=$request->input($key);CourseApplicationAnswer::updateOrCreate(['course_application_id'=>$application->id,'course_call_question_id'=>$question->id],['answer_text'=>is_array($value)?null:$value,'answer_json'=>is_array($value)?$value:null]);} if($request->boolean('submit')){$application->update(['status'=>'submitted','submitted_at'=>now(),'reviewed_at'=>null,'reviewed_by'=>null]);}else{$application->update(['status'=>'draft','submitted_at'=>in_array($application->status,$revision,true)?null:$application->submitted_at]);}}); return redirect()->route('participant.course-calls.index',['tab'=>'submissions'])->with('success',$request->boolean('submit')?'Application submitted.':'Application draft saved.'); }
}