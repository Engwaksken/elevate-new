<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GradebookController extends Controller
{
    public function index(Request $request, Course $course)
    {
        $query=AssessmentAttempt::with(['assessment','assessment.questions','user'])
            ->whereHas('assessment',fn($q)=>$q->where('course_id',$course->id))
            ->latest();

        if($search=trim((string)$request->get('search'))){
            $query->where(function($q) use($search){
                $q->whereHas('user',fn($u)=>$u
                    ->where('name','like',"%{$search}%")
                    ->orWhere('email','like',"%{$search}%"))
                  ->orWhereHas('assessment',fn($a)=>$a->where('title','like',"%{$search}%"));
            });
        }

        if($status=$request->get('status')) $query->where('status',$status);

        $perPage=in_array((int)$request->get('per_page'),[10,20,30,50,100],true)
            ? (int)$request->get('per_page') : 30;

        return view('admin.elearning.gradebook.index',[
            'course'=>$course,
            'attempts'=>$query->paginate($perPage)->withQueryString(),
            'stats'=>[
                'total'=>AssessmentAttempt::whereHas('assessment',fn($q)=>$q->where('course_id',$course->id))->count(),
                'submitted'=>AssessmentAttempt::whereHas('assessment',fn($q)=>$q->where('course_id',$course->id))->where('status','submitted')->count(),
                'graded'=>AssessmentAttempt::whereHas('assessment',fn($q)=>$q->where('course_id',$course->id))->where('status','graded')->count(),
                'average'=>round((float)AssessmentAttempt::whereHas('assessment',fn($q)=>$q->where('course_id',$course->id))->whereNotNull('percentage')->avg('percentage'),2),
            ],
        ]);
    }

    public function edit(AssessmentAttempt $attempt)
    {
        $attempt->load(['assessment.questions','user']);

        $answers=AssessmentAnswer::where('assessment_attempt_id',$attempt->id)
            ->get()->keyBy('assessment_question_id');

        return view('admin.elearning.gradebook.edit',compact('attempt','answers'));
    }

    public function update(Request $request, AssessmentAttempt $attempt)
    {
        $attempt->load('assessment.questions');

        DB::transaction(function() use($request,$attempt){
            $totalAwarded=0;
            $totalPossible=0;

            foreach($attempt->assessment->questions as $question){
                $totalPossible+=(float)$question->marks;
                $marks=(float)$request->input("marks.{$question->id}",0);
                $marks=max(0,min($marks,(float)$question->marks));
                $totalAwarded+=$marks;

                AssessmentAnswer::where('assessment_attempt_id',$attempt->id)
                    ->where('assessment_question_id',$question->id)
                    ->update([
                        'awarded_marks'=>$marks,
                        'grader_feedback'=>$request->input("feedback.{$question->id}"),
                    ]);
            }

            $percentage=$totalPossible>0
                ? round(($totalAwarded/$totalPossible)*100,2)
                : 0;

            $attempt->update([
                'score'=>$totalAwarded,
                'percentage'=>$percentage,
                'status'=>'graded',
                'graded_at'=>now(),
                'graded_by'=>auth()->id(),
            ]);
        });

        return redirect()
            ->route('admin.elearning.gradebook.index',$attempt->assessment->course_id)
            ->with('success','Attempt graded.');
    }
}
