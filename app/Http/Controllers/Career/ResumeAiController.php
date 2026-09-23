<?php

namespace App\Http\Controllers\Career;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Services\CareerAiService;
use Illuminate\Http\Request;

class ResumeAiController extends Controller {
    private function own(Resume $resume){ abort_unless($resume->user_id===auth()->id(),403); }

    public function improve(Resume $resume,CareerAiService $ai){
        $this->own($resume); $resume->load(['experiences','education','skills','certifications','projects','languages']);
        try{$r=$ai->generate('resume_improvement','Review this resume and give concise professional improvements. Never invent facts, numbers, qualifications, skills or employment.',json_encode($resume->toArray()),auth()->id());}
        catch(\RuntimeException){return back()->with('warning','AI assistance is temporarily unavailable. You can continue editing manually.');}
        return back()->with('ai_suggestion',$r['text']);
    }

    public function ats(Resume $resume,CareerAiService $ai){
        $this->own($resume); $resume->load(['experiences','education','skills']);
        try{$r=$ai->generate('ats_review','Provide ATS-readiness guidance only. Check headings, readability, keywords, skills and experience. Never guarantee a score.',json_encode($resume->toArray()),auth()->id());}
        catch(\RuntimeException){return back()->with('warning','AI assistance is temporarily unavailable. You can continue editing manually.');}
        return back()->with('ai_suggestion',$r['text']);
    }

    public function tailor(Request $request,Resume $resume,CareerAiService $ai){
        $this->own($resume); $data=$request->validate(['job_description'=>['required','string','max:15000']]);
        $resume->load(['experiences','education','skills']);
        try{$r=$ai->generate('job_tailoring','Compare the resume with the job description. Identify strong matches, gaps and truthful improvements. Never add unprovided qualifications or experience.',json_encode(['resume'=>$resume->toArray(),'job_description'=>$data['job_description']]),auth()->id());}
        catch(\RuntimeException){return back()->with('warning','AI assistance is temporarily unavailable. You can continue editing manually.');}
        return back()->with('ai_suggestion',$r['text']);
    }
}
