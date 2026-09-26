<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Cohort,Course,Programme,Project,Survey,SurveyAssignment,SurveyQuestion,User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SurveyController extends Controller
{
    private const TYPES=[
        'short_text','long_text','single_choice','multiple_choice','dropdown',
        'yes_no','rating','likert','number','date','time','email','phone',
        'location','file','image','matrix','consent','heading','description'
    ];

    public function index(Request $request)
    {
        $q=Survey::withCount('responses')->latest();

        if($s=trim((string)$request->search)) {
            $q->where('title','like',"%{$s}%");
        }

        if($request->status) {
            $q->where('status',$request->status);
        }

        return view('admin.surveys.index',[
            'surveys'=>$q->paginate(20)->withQueryString(),
            'courses'=>Course::orderBy('title')->get(),
            'cohorts'=>Cohort::orderBy('name')->get(),
            'programmes'=>Programme::orderBy('name')->get(),
            'projects'=>Project::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $d=$request->validate([
            'title'=>'required|string|max:255',
            'description'=>'nullable|string',
            'access_type'=>'required|in:public,authenticated,course,cohort,selected',
            'course_id'=>'nullable|exists:courses,id',
            'cohort_id'=>'nullable|exists:cohorts,id',
            'programme_id'=>'nullable|exists:programmes,id',
            'project_id'=>'nullable|exists:projects,id',
            'allow_draft'=>'nullable|boolean',
            'anonymous_allowed'=>'nullable|boolean',
            'response_limit'=>'nullable|integer|min:1',
            'opens_at'=>'nullable|date',
            'closes_at'=>'nullable|date|after_or_equal:opens_at',
            'status'=>'required|in:draft,published,closed,archived'
        ]);

        $d['slug']=Str::slug($d['title']).'-'.Str::lower(Str::random(6));
        $d['created_by']=auth()->id();
        $d['allow_draft']=$request->boolean('allow_draft');
        $d['anonymous_allowed']=$request->boolean('anonymous_allowed');

        $survey=Survey::create($d);

        return redirect()
            ->route('admin.surveys.builder',$survey)
            ->with('success','Survey created.');
    }

    public function builder(Survey $survey)
    {
        return view('admin.surveys.builder',[
            'survey'=>$survey->load(['sections.questions','questions','assignments.user']),
            'questionTypes'=>self::TYPES,
            'participants'=>User::query()
                ->where('user_type','participant')
                ->where('status','active')
                ->orderBy('name')
                ->limit(1000)
                ->get(['id','name','email']),
        ]);
    }

    public function addSection(Request $request,Survey $survey)
    {
        $d=$request->validate([
            'title'=>'required|string|max:255',
            'description'=>'nullable|string',
        ]);

        $survey->sections()->create(array_merge($d,[
            'position'=>$survey->sections()->max('position')+1,
        ]));

        return back()->with('success','Section added.');
    }

    public function addQuestion(Request $request,Survey $survey)
    {
        $d=$request->validate([
            'survey_section_id'=>'nullable|exists:survey_sections,id',
            'question_type'=>'required|in:'.implode(',',self::TYPES),
            'question_text'=>'required|string',
            'hint'=>'nullable|string',
            'options_text'=>'nullable|string',
            'is_required'=>'nullable|boolean',
            'condition_question_id'=>'nullable|integer|exists:survey_questions,id',
            'condition_operator'=>'nullable|in:equals,not_equals,contains,not_empty',
            'condition_value'=>'nullable|string|max:1000',
        ]);

        if (
            $d['survey_section_id']
            && ! $survey->sections()->whereKey($d['survey_section_id'])->exists()
        ) {
            abort(422,'Invalid survey section.');
        }

        if (! empty($d['condition_question_id'])) {
            $parent=SurveyQuestion::findOrFail($d['condition_question_id']);

            abort_unless(
                (int)$parent->survey_id === (int)$survey->id,
                422,
                'Conditional question must belong to this survey.'
            );
        }

        $options=$d['options_text']
            ? collect(preg_split('/\r\n|\r|\n/',$d['options_text']))
                ->map(fn($v)=>trim($v))
                ->filter()
                ->values()
                ->all()
            : null;

        $logic=null;

        if (! empty($d['condition_question_id'])) {
            $logic=[
                'question_id'=>(int)$d['condition_question_id'],
                'operator'=>$d['condition_operator'] ?? 'equals',
                'value'=>$d['condition_value'] ?? '',
            ];
        }

        $survey->questions()->create([
            'survey_section_id'=>$d['survey_section_id']?:null,
            'question_type'=>$d['question_type'],
            'question_text'=>$d['question_text'],
            'hint'=>$d['hint']??null,
            'options'=>$options,
            'conditional_logic'=>$logic,
            'is_required'=>$request->boolean('is_required'),
            'position'=>$survey->questions()->max('position')+1,
        ]);

        return back()->with('success','Survey question added.');
    }

    public function destroyQuestion(Survey $survey,SurveyQuestion $question)
    {
        abort_unless((int)$question->survey_id===(int)$survey->id,404);

        abort_if(
            $survey->responses()->where('status','submitted')->exists(),
            422,
            'Questions cannot be deleted after submitted responses exist.'
        );

        $question->delete();

        return back()->with('success','Question deleted.');
    }

    public function reorder(Request $request,Survey $survey)
    {
        $d=$request->validate([
            'question_ids'=>'required|array',
            'question_ids.*'=>'integer',
        ]);

        $owned=$survey->questions()
            ->whereIn('id',$d['question_ids'])
            ->pluck('id')
            ->map(fn($id)=>(int)$id)
            ->sort()
            ->values();

        $submitted=collect($d['question_ids'])
            ->map(fn($id)=>(int)$id)
            ->unique()
            ->sort()
            ->values();

        if ($owned->values()->all() !== $submitted->values()->all()) {
            throw ValidationException::withMessages([
                'question_ids'=>'Invalid question order submitted.',
            ]);
        }

        foreach($d['question_ids'] as $index=>$id) {
            SurveyQuestion::whereKey($id)->update(['position'=>$index+1]);
        }

        return response()->json(['ok'=>true]);
    }

    public function assignments(Request $request,Survey $survey)
    {
        $d=$request->validate([
            'participants'=>'nullable|array',
            'participants.*'=>'integer|exists:users,id',
        ]);

        $participantIds=User::query()
            ->where('user_type','participant')
            ->whereIn('id',$d['participants'] ?? [])
            ->pluck('id')
            ->all();

        $survey->assignments()->delete();

        foreach($participantIds as $userId) {
            SurveyAssignment::create([
                'survey_id'=>$survey->id,
                'user_id'=>$userId,
            ]);
        }

        if ($survey->access_type !== 'selected') {
            $survey->update(['access_type'=>'selected']);
        }

        return back()->with('success','Survey participant assignments updated.');
    }

    public function responses(Survey $survey)
    {
        $survey->load('questions');

        $responses=$survey->responses()
            ->with(['user','answers.question'])
            ->latest()
            ->paginate(30);

        $stats=[
            'total'=>$survey->responses()->count(),
            'submitted'=>$survey->responses()->where('status','submitted')->count(),
            'draft'=>$survey->responses()->where('status','draft')->count(),
            'unique_participants'=>$survey->responses()
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
        ];

        return view('admin.surveys.responses',compact('survey','responses','stats'));
    }

    public function exportCsv(Survey $survey)
    {
        abort_unless(
            auth()->user()->isSuperAdmin()
            || auth()->user()->hasPermission('survey_responses.export'),
            403
        );

        $survey->load('questions');

        $filename=Str::slug($survey->title).'-responses-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function() use($survey) {
            $handle=fopen('php://output','w');
            fwrite($handle,"\xEF\xBB\xBF");

            $headers=['response_id','participant_name','participant_email','status','submitted_at'];

            foreach($survey->questions as $q) {
                $headers[]='Q'.$q->id.' '.$q->question_text;
            }

            fputcsv($handle,$headers);

            $survey->responses()
                ->with(['user','answers'])
                ->orderBy('id')
                ->chunkById(200,function($responses) use($handle,$survey) {
                    foreach($responses as $response) {
                        $answers=$response->answers->keyBy('survey_question_id');

                        $row=[
                            $response->id,
                            $response->user?->name ?? 'Anonymous',
                            $response->user?->email ?? '',
                            $response->status,
                            optional($response->submitted_at)->format('Y-m-d H:i:s'),
                        ];

                        foreach($survey->questions as $q) {
                            $a=$answers->get($q->id);
                            $row[]=$a
                                ? ($a->answer_text ?? implode(', ',$a->answer_json ?? []))
                                : '';
                        }

                        fputcsv($handle,$row);
                    }
                });

            fclose($handle);
        },$filename,['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
