<?php

namespace App\Http\Controllers\Career;

use App\Http\Controllers\Controller;
use App\Models\CoverLetter;
use App\Models\Resume;
use App\Services\CareerAiService;
use Illuminate\Http\Request;

class CoverLetterController extends Controller {
    public function store(Request $request){
        $d=$request->validate([
            'title'=>['required','string','max:190'],'resume_id'=>['nullable','integer'],'job_id'=>['nullable','integer'],
            'employer_name'=>['nullable','string','max:190'],'job_title'=>['nullable','string','max:190'],
            'recipient_name'=>['nullable','string','max:190'],'body'=>['nullable','string']
        ]);
        if(!empty($d['resume_id'])) abort_unless(Resume::whereKey($d['resume_id'])->where('user_id',auth()->id())->exists(),403);
        CoverLetter::create($d+['user_id'=>auth()->id(),'source'=>'manual']);
        return back()->with('success','Cover letter saved.');
    }

    public function generate(Request $request,CareerAiService $ai){
        $d=$request->validate([
            'resume_id'=>['required','integer'],'job_title'=>['required','string','max:190'],
            'employer_name'=>['nullable','string','max:190'],'job_description'=>['required','string','max:15000'],
            'tone'=>['nullable','in:professional,confident,warm,concise']
        ]);
        $resume=Resume::whereKey($d['resume_id'])->where('user_id',auth()->id())->with(['experiences','education','skills'])->firstOrFail();
        try{$r=$ai->generate('cover_letter_generation','Write a truthful professional cover letter using only supplied facts. Do not invent qualifications, achievements, employers or numbers.',json_encode(['resume'=>$resume->toArray(),'job'=>$d]),auth()->id());}
        catch(\RuntimeException){return back()->with('warning','AI assistance is temporarily unavailable. You can continue editing manually.');}
        CoverLetter::create([
            'user_id'=>auth()->id(),'resume_id'=>$resume->id,'title'=>$d['job_title'].' Cover Letter',
            'employer_name'=>$d['employer_name']??null,'job_title'=>$d['job_title'],'body'=>$r['text'],'source'=>'ai','ai_generated'=>true
        ]);
        return back()->with('success','Cover letter draft generated. Please review every detail.');
    }
}
