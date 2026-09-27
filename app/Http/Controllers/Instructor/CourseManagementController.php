<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrolment;
use App\Models\Lesson;
use Illuminate\Http\Request;

class CourseManagementController extends Controller
{
    public function show(Request $request, Course $course)
    {
        $this->authorise($course);
        $course->load(['modules.lessons','assessments.questions','cohorts']);

        $participants = $course->enrolments()
            ->with(['user','cohort'])
            ->when($request->filled('participant_search'), function ($query) use ($request) {
                $search = trim((string) $request->get('participant_search'));
                $query->whereHas('user', fn ($user) => $user
                    ->where('name','like',"%{$search}%")
                    ->orWhere('email','like',"%{$search}%"));
            })
            ->latest()
            ->paginate(20, ['*'], 'participants_page')
            ->withQueryString();

        return view('instructor.course-manage', [
            'course' => $course,
            'participants' => $participants,
            'stats' => [
                'modules' => $course->modules->count(),
                'lessons' => $course->modules->sum(fn ($module) => $module->lessons->count()),
                'assessments' => $course->assessments->count(),
                'participants' => $course->enrolments()->count(),
            ],
        ]);
    }

    public function storeModule(Request $request, Course $course)
    {
        $this->authorise($course);
        $data = $request->validate([
            'title'=>['required','string','max:190'],
            'description'=>['nullable','string'],
            'position'=>['nullable','integer','min:1'],
            'is_published'=>['nullable','boolean'],
        ]);
        $course->modules()->create([
            'title'=>$data['title'],
            'description'=>$data['description'] ?? null,
            'position'=>$data['position'] ?? (($course->modules()->max('position') ?? 0)+1),
            'is_published'=>$request->boolean('is_published'),
        ]);
        return back()->with('success','Module added.');
    }

    public function destroyModule(Course $course, CourseModule $module)
    {
        $this->authorise($course);
        abort_unless((int)$module->course_id === (int)$course->id,404);
        $module->delete();
        return back()->with('success','Module deleted.');
    }

    public function storeLesson(Request $request, Course $course, CourseModule $module)
    {
        $this->authorise($course);
        abort_unless((int)$module->course_id === (int)$course->id,404);
        $data=$request->validate([
            'title'=>['required','string','max:190'],
            'content'=>['nullable','string'],
            'content_type'=>['required','in:text,video,file,link,mixed'],
            'video_url'=>['nullable','url'],
            'external_url'=>['nullable','url'],
            'estimated_minutes'=>['nullable','integer','min:1'],
            'is_published'=>['nullable','boolean'],
        ]);
        $module->lessons()->create($data+['is_published'=>$request->boolean('is_published')]);
        return back()->with('success','Lesson added.');
    }

    public function destroyLesson(Course $course, CourseModule $module, Lesson $lesson)
    {
        $this->authorise($course);
        abort_unless(
            (int)$module->course_id === (int)$course->id
            && (int)$lesson->course_module_id === (int)$module->id,
            404
        );
        $lesson->delete();
        return back()->with('success','Lesson deleted.');
    }

    public function storeAssessment(Request $request, Course $course)
    {
        $this->authorise($course);
        $course->assessments()->create($request->validate([
            'title'=>['required','string','max:190'],
            'type'=>['required','in:quiz,assignment,exam'],
            'instructions'=>['nullable','string'],
            'pass_mark'=>['required','numeric','min:0','max:100'],
            'max_attempts'=>['required','integer','min:1','max:20'],
            'is_published'=>['nullable','boolean'],
        ])+['is_published'=>$request->boolean('is_published')]);
        return back()->with('success','Assessment added.');
    }

    public function addQuestion(Request $request, Course $course, Assessment $assessment)
    {
        $this->authorise($course);
        abort_unless((int)$assessment->course_id === (int)$course->id,404);

        $data=$request->validate([
            'question_type'=>['required','in:multiple_choice,true_false,short_text,long_text'],
            'question_text'=>['required','string'],
            'marks'=>['required','numeric','min:0.1'],
            'options_text'=>['nullable','string'],
            'correct_value'=>['nullable','string'],
        ]);

        $options=null;
        if($data['question_type']==='true_false'){
            $options=['true'=>'True','false'=>'False'];
        } elseif($data['question_type']==='multiple_choice'){
            $options=collect(preg_split('/\r\n|\r|\n/',(string)($data['options_text']??'')))
                ->map(fn($v)=>trim((string)$v))->filter()->values()
                ->mapWithKeys(fn($v,$i)=>[(string)$i=>$v])->all();
            if(count($options)<2){
                return back()->withErrors(['options_text'=>'Multiple-choice questions require at least two options.'])->withInput();
            }
        }

        $assessment->questions()->create([
            'question_type'=>$data['question_type'],
            'question_text'=>$data['question_text'],
            'options'=>$options,
            'correct_answer'=>filled($data['correct_value']??null)?['value'=>$data['correct_value']]:null,
            'marks'=>$data['marks'],
            'position'=>($assessment->questions()->max('position')??0)+1,
        ]);

        return back()->with('success','Question added.');
    }

    public function destroyQuestion(Course $course, Assessment $assessment, AssessmentQuestion $question)
    {
        $this->authorise($course);
        abort_unless(
            (int)$assessment->course_id === (int)$course->id
            && (int)$question->assessment_id === (int)$assessment->id,
            404
        );
        $question->delete();
        return back()->with('success','Question deleted.');
    }

    public function updateParticipant(Request $request, Course $course, Enrolment $enrolment)
    {
        $this->authorise($course);
        abort_unless((int)$enrolment->course_id === (int)$course->id,404);

        $enrolment->update($request->validate([
            'status'=>['required','in:enrolled,active,in_progress,completed,withdrawn,cancelled'],
            'progress_percent'=>['nullable','numeric','min:0','max:100'],
            'final_score'=>['nullable','numeric','min:0','max:100'],
        ]));

        return back()->with('success','Participant progress updated.');
    }

    private function authorise(Course $course): void
    {
        abort_unless(
            $course->instructors()->where('users.id',auth()->id())->exists()
            || auth()->user()?->isSuperAdmin(),
            403
        );
    }
}
