<?php
namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\{Enrolment,Survey,SurveyAnswer,SurveyResponse};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SurveyResponseController extends Controller
{
    public function index()
    {
        $user=auth()->user();
        $courseIds=Enrolment::where('user_id',$user->id)->pluck('course_id');
        $cohortIds=Enrolment::where('user_id',$user->id)
            ->whereNotNull('cohort_id')
            ->pluck('cohort_id');

        $surveys=Survey::where('status','published')
            ->where(fn($q)=>$q->whereNull('opens_at')->orWhere('opens_at','<=',now()))
            ->where(fn($q)=>$q->whereNull('closes_at')->orWhere('closes_at','>=',now()))
            ->where(function($q) use($user,$courseIds,$cohortIds){
                $q->where('access_type','authenticated')
                    ->orWhere(fn($x)=>$x->where('access_type','course')->whereIn('course_id',$courseIds))
                    ->orWhere(fn($x)=>$x->where('access_type','cohort')->whereIn('cohort_id',$cohortIds))
                    ->orWhere(fn($x)=>$x->where('access_type','selected')
                        ->whereHas('assignments',fn($a)=>$a->where('user_id',$user->id)));
            })
            ->latest()
            ->paginate(12);

        return view('participant.surveys.index',compact('surveys'));
    }

    public function show(Survey $survey)
    {
        abort_unless($survey->isOpen(),404);
        $this->authorizeSurvey($survey);

        $survey->load(['sections.questions','questions']);

        $response=SurveyResponse::firstOrCreate(
            [
                'survey_id'=>$survey->id,
                'user_id'=>auth()->id(),
                'status'=>'draft',
            ],
            ['started_at'=>now()]
        );

        $response->load('answers');

        return view('participant.surveys.show',compact('survey','response'));
    }

    public function save(Request $request,Survey $survey)
    {
        abort_unless($survey->isOpen(),422,'Survey is closed.');
        $this->authorizeSurvey($survey);

        if (
            $survey->response_limit
            && $survey->responses()->where('status','submitted')->count() >= $survey->response_limit
        ) {
            throw ValidationException::withMessages([
                'survey'=>'This survey has reached its response limit.',
            ]);
        }

        $response=SurveyResponse::firstOrCreate(
            [
                'survey_id'=>$survey->id,
                'user_id'=>auth()->id(),
                'status'=>'draft',
            ],
            ['started_at'=>now()]
        );

        DB::transaction(function() use($request,$survey,$response){
            $submittedAnswers=[];

            foreach($survey->questions()->orderBy('position')->get() as $question) {
                $key='question_'.$question->id;
                $visible=$this->questionIsVisible(
                    $question->conditional_logic,
                    $request,
                    $submittedAnswers
                );

                if (
                    $visible
                    && $question->is_required
                    && $request->boolean('submit')
                    && ! in_array($question->question_type,['heading','description'],true)
                ) {
                    $request->validate([$key=>'required']);
                }

                if (! $visible) {
                    SurveyAnswer::where([
                        'survey_response_id'=>$response->id,
                        'survey_question_id'=>$question->id,
                    ])->delete();

                    continue;
                }

                $value=$request->input($key);
                $submittedAnswers[$question->id]=$value;

                SurveyAnswer::updateOrCreate(
                    [
                        'survey_response_id'=>$response->id,
                        'survey_question_id'=>$question->id,
                    ],
                    [
                        'answer_text'=>is_array($value)?null:$value,
                        'answer_json'=>is_array($value)?$value:null,
                    ]
                );
            }

            if($request->boolean('submit')) {
                $response->update([
                    'status'=>'submitted',
                    'submitted_at'=>now(),
                ]);
            }
        });

        return $request->boolean('submit')
            ? redirect()->route('participant.surveys.index')->with('success','Survey submitted.')
            : back()->with('success','Survey draft saved.');
    }

    private function questionIsVisible(?array $logic,Request $request,array $answers): bool
    {
        if (! $logic || empty($logic['question_id'])) {
            return true;
        }

        $parentId=(int)$logic['question_id'];
        $actual=$answers[$parentId] ?? $request->input('question_'.$parentId);
        $expected=(string)($logic['value'] ?? '');
        $operator=$logic['operator'] ?? 'equals';

        if (is_array($actual)) {
            return match($operator) {
                'contains' => in_array($expected,$actual,true),
                'not_equals' => ! in_array($expected,$actual,true),
                'not_empty' => count($actual)>0,
                default => in_array($expected,$actual,true),
            };
        }

        $actual=(string)($actual ?? '');

        return match($operator) {
            'not_equals' => $actual !== $expected,
            'contains' => str_contains($actual,$expected),
            'not_empty' => trim($actual) !== '',
            default => $actual === $expected,
        };
    }

    private function authorizeSurvey(Survey $survey): void
    {
        $user=auth()->user();

        if($survey->access_type==='authenticated') return;

        if(
            $survey->access_type==='course'
            && Enrolment::where('user_id',$user->id)
                ->where('course_id',$survey->course_id)
                ->exists()
        ) return;

        if(
            $survey->access_type==='cohort'
            && Enrolment::where('user_id',$user->id)
                ->where('cohort_id',$survey->cohort_id)
                ->exists()
        ) return;

        if(
            $survey->access_type==='selected'
            && $survey->assignments()->where('user_id',$user->id)->exists()
        ) return;

        abort(403);
    }
}
