<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\Course;
use Illuminate\Http\Request;

class AssessmentBuilderController extends Controller
{
    use ExportsTables;
    public function index(Request $request, Course $course)
    {
        $query=$course->assessments()->withCount('questions')->latest();

        if($search=trim((string)$request->get('search'))){
            $query->where('title','like',"%{$search}%");
        }

        if($type=$request->get('type')) $query->where('type',$type);

        if($request->filled('published')){
            $query->where('is_published',$request->boolean('published'));
        }

        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'Assessments - '.$course->title,$query,[
                'Assessment'=>'title',
                'Type'=>'type',
                'Questions'=>'questions_count',
                'Pass mark'=>'pass_mark',
                'Max attempts'=>'max_attempts',
                'Opens'=>'opens_at',
                'Due'=>'due_at',
                'Status'=>fn($a)=>$a->is_published?'Published':'Draft',
            ]);
        }

        $assessments=$query->paginate(
            in_array((int)$request->get('per_page'),[10,20,25,50],true)
                ? (int)$request->get('per_page') : 20
        )->withQueryString();

        return view('admin.elearning.assessments.index',[
            'course'=>$course,
            'assessments'=>$assessments,
            'stats'=>[
                'total'=>$course->assessments()->count(),
                'published'=>$course->assessments()->where('is_published',true)->count(),
                'draft'=>$course->assessments()->where('is_published',false)->count(),
                'questions'=>AssessmentQuestion::whereHas('assessment',fn($q)=>$q->where('course_id',$course->id))->count(),
            ],
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $data=$request->validate([
            'title'=>['required','string','max:190'],
            'type'=>['required','in:quiz,assignment,exam'],
            'instructions'=>['nullable','string'],
            'pass_mark'=>['required','numeric','min:0','max:100'],
            'max_attempts'=>['required','integer','min:1','max:20'],
            'opens_at'=>['nullable','date'],
            'due_at'=>['nullable','date','after_or_equal:opens_at'],
            'is_published'=>['nullable','boolean'],
            'publish'=>['nullable','boolean'],
            'submission_formats'=>['nullable','array'],
            'submission_formats.*'=>['string','in:'.implode(',',array_keys(Assessment::SUBMISSION_FORMATS))],
            'max_submission_files'=>['nullable','integer','min:1','max:'.Assessment::MAX_SUBMISSION_FILES_LIMIT],
            'allow_link_submissions'=>['nullable','boolean'],
        ]);

        $published=$request->has('publish') ? $request->boolean('publish') : $request->boolean('is_published');
        $settings=$data['type']==='assignment' ? [
            'submission_formats'=>array_values(array_unique($data['submission_formats'] ?? [])) ?: Assessment::DEFAULT_SUBMISSION_FORMATS,
            'max_submission_files'=>(int)($data['max_submission_files'] ?? 1),
            'allow_link_submissions'=>(bool)($data['allow_link_submissions'] ?? false),
        ] : [];

        $assessment=$course->assessments()->create(
            collect($data)->except(['publish','submission_formats','max_submission_files','allow_link_submissions'])->all()
            +['is_published'=>$published]+$settings
        );

        return redirect()->route('admin.elearning.assessments.edit',[$course,$assessment])
            ->with('success',$published ? 'Assessment created and published.' : 'Assessment saved as a draft. Participants will not see it until it is published.');
    }

    /** Publish a draft, or move a published assessment back to drafts. */
    public function togglePublish(Request $request, Course $course, Assessment $assessment)
    {
        abort_unless($assessment->course_id===$course->id,404);

        $assessment->update(['is_published'=>$request->boolean('publish')]);

        return back()->with('success',$assessment->is_published
            ? "\"{$assessment->title}\" published. Participants can see it now."
            : "\"{$assessment->title}\" moved to drafts. Participants can no longer see it.");
    }

    public function edit(Course $course, Assessment $assessment)
    {
        abort_unless($assessment->course_id===$course->id,404);

        return view('admin.elearning.assessments.edit',[
            'course'=>$course,
            'assessment'=>$assessment->load('questions'),
        ]);
    }

    public function addQuestion(Request $request, Course $course, Assessment $assessment)
    {
        abort_unless($assessment->course_id===$course->id,404);

        $data=$request->validate([
            'question_type'=>['required','in:multiple_choice,true_false,short_text,long_text'],
            'question_text'=>['required','string'],
            'marks'=>['required','numeric','min:0.1'],
            'position'=>['nullable','integer','min:1'],
            'options_text'=>['nullable','string'],
            'correct_value'=>['nullable','string'],
        ]);

        $options=null;
        if(in_array($data['question_type'],['multiple_choice','true_false'],true)){
            $options=$data['question_type']==='true_false'
                ? ['true'=>'True','false'=>'False']
                : collect(preg_split('/\r\n|\r|\n/',(string)($data['options_text'] ?? '')))
                    ->map(fn($v)=>trim((string)$v))
                    ->filter()
                    ->values()
                    ->mapWithKeys(fn($v,$i)=>[(string)$i=>$v])
                    ->all();

            if($data['question_type']==='multiple_choice' && count($options)<2){
                return back()->withErrors(['options_text'=>'Multiple-choice questions require at least two options.'])->withInput();
            }
        }

        $assessment->questions()->create([
            'question_type'=>$data['question_type'],
            'question_text'=>$data['question_text'],
            'options'=>$options,
            'correct_answer'=>isset($data['correct_value']) && $data['correct_value']!==''
                ? ['value'=>$data['correct_value']]
                : null,
            'marks'=>$data['marks'],
            'position'=>$data['position'] ?? (($assessment->questions()->max('position') ?? 0)+1),
        ]);

        return back()->with('success','Question added.');
    }

    public function destroyQuestion(Course $course, Assessment $assessment, AssessmentQuestion $question)
    {
        abort_unless(
            $assessment->course_id===$course->id
            && $question->assessment_id===$assessment->id,
            404
        );

        $question->delete();

        return back()->with('success','Question deleted.');
    }
}
