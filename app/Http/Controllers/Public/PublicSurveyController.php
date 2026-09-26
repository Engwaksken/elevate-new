<?php
namespace App\Http\Controllers\Public;
use App\Http\Controllers\Controller;
use App\Models\{Survey,SurveyAnswer,SurveyResponse};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class PublicSurveyController extends Controller {
    public function show(Survey $survey){
        abort_unless($survey->access_type==='public' && $survey->isOpen(),404);
        return view('public.surveys.show',['survey'=>$survey->load(['sections.questions','questions'])]);
    }
    public function store(Request $request,Survey $survey){
        abort_unless($survey->access_type==='public' && $survey->isOpen(),404);
        DB::transaction(function() use($request,$survey){
            $response=SurveyResponse::create([
                'survey_id'=>$survey->id,'user_id'=>auth()->id(),'respondent_token'=>Str::uuid(),
                'status'=>'submitted','started_at'=>now(),'submitted_at'=>now()
            ]);
            foreach($survey->questions as $question){
                $key='question_'.$question->id;
                if($question->is_required) $request->validate([$key=>'required']);
                $value=$request->input($key);
                SurveyAnswer::create([
                    'survey_response_id'=>$response->id,'survey_question_id'=>$question->id,
                    'answer_text'=>is_array($value)?null:$value,'answer_json'=>is_array($value)?$value:null
                ]);
            }
        });
        return back()->with('success','Thank you. Your response has been submitted.');
    }
    public function qr(Survey $survey){
        abort_unless($survey->status==='published',404);
        $url=route('surveys.public.show',$survey);
        if(class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)){
            return response(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(320)->generate($url),200,['Content-Type'=>'image/svg+xml']);
        }
        abort(503,'QR renderer is not installed. The survey link remains available.');
    }
}