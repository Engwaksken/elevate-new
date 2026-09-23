<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiIntegration;
use App\Models\AiUsageLog;
use Illuminate\Http\Request;

class CareerAiController extends Controller {
    public function index(){
        return view('admin.career-ai.index',['integrations'=>AiIntegration::orderBy('feature')->get(),'usage'=>AiUsageLog::latest()->paginate(30)]);
    }

    public function update(Request $request){
        $d=$request->validate([
            'feature'=>['required','string','max:100'],'provider'=>['required','in:openai,gemini,compatible'],
            'model'=>['nullable','string','max:190'],'endpoint'=>['nullable','url','max:500'],
            'api_key'=>['nullable','string','max:1000'],'enabled'=>['nullable','boolean'],
            'daily_limit'=>['nullable','integer','min:1','max:100000'],'user_daily_limit'=>['nullable','integer','min:1','max:1000']
        ]);
        $i=AiIntegration::firstOrNew(['feature'=>$d['feature']]);
        $i->fill([
            'provider'=>$d['provider'],'model'=>$d['model']??null,'endpoint'=>$d['endpoint']??null,'enabled'=>$request->boolean('enabled'),
            'settings'=>['daily_limit'=>$d['daily_limit']??1000,'user_daily_limit'=>$d['user_daily_limit']??20]
        ]);
        $i->setApiKey($d['api_key']??null); $i->save();
        return back()->with('success','Career AI configuration updated.');
    }
}
