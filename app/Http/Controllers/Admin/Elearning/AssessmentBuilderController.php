<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\Course;
use Illuminate\Http\Request;

class AssessmentBuilderController extends Controller
{
    public function index(Course $course)
    {
        return view('admin.elearning.assessments.index', [
            'course'=>$course,
            'assessments'=>$course->assessments()->withCount('questions')->latest()->get(),
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $assessment = $course->assessments()->create($request->validate([
            'title'=>['required','string','max:190'],
            'type'=>['required','in:quiz,assignment,exam'],
            'instructions'=>['nullable','string'],
            'pass_mark'=>['required','numeric','min:0','max:100'],
            'max_attempts'=>['required','integer','min:1','max:20'],
            'opens_at'=>['nullable','date'],
            'due_at'=>['nullable','date','after_or_equal:opens_at'],
            'is_published'=>['nullable','boolean'],
        ]) + ['is_published'=>$request->boolean('is_published')]);

        return redirect()->route('admin.elearning.assessments.edit',[$course,$assessment])
            ->with('success','Assessment created.');
    }

    public function edit(Course $course, Assessment $assessment)
    {
        abort_unless($assessment->course_id === $course->id,404);

        return view('admin.elearning.assessments.edit', [
            'course'=>$course,
            'assessment'=>$assessment->load('questions'),
        ]);
    }

    public function addQuestion(Request $request, Course $course, Assessment $assessment)
    {
        abort_unless($assessment->course_id === $course->id,404);

        $data = $request->validate([
            'question_type'=>['required','in:multiple_choice,true_false,short_text,long_text'],
            'question_text'=>['required','string'],
            'marks'=>['required','numeric','min:0.1'],
            'position'=>['nullable','integer','min:1'],
            'options_text'=>['nullable','string'],
            'correct_value'=>['nullable','string'],
        ]);

        $options = null;
        if (in_array($data['question_type'],['multiple_choice','true_false'],true)) {
            $options = $data['question_type'] === 'true_false'
                ? ['true'=>'True','false'=>'False']
                : collect(preg_split('/\r\n|\r|\n/', (string)($data['options_text'] ?? '')))
                    ->filter()->values()->mapWithKeys(fn($v,$i)=>[(string)$i=>$v])->all();
        }

        $assessment->questions()->create([
            'question_type'=>$data['question_type'],
            'question_text'=>$data['question_text'],
            'options'=>$options,
            'correct_answer'=>isset($data['correct_value']) ? ['value'=>$data['correct_value']] : null,
            'marks'=>$data['marks'],
            'position'=>$data['position'] ?? ($assessment->questions()->max('position') + 1),
        ]);

        return back()->with('success','Question added.');
    }

    public function destroyQuestion(Course $course, Assessment $assessment, AssessmentQuestion $question)
    {
        abort_unless($assessment->course_id === $course->id && $question->assessment_id === $assessment->id,404);
        $question->delete();
        return back()->with('success','Question deleted.');
    }
}
