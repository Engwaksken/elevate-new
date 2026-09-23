<?php

namespace App\Jobs;

use App\Models\ResumeUpload;
use App\Services\CareerAiService;
use App\Services\DocumentTextExtractor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ParseResumeUpload implements ShouldQueue {
    use Queueable;
    public function __construct(public int $uploadId){}

    public function handle(DocumentTextExtractor $extractor,CareerAiService $ai):void {
        $u=ResumeUpload::findOrFail($this->uploadId); $u->update(['status'=>'processing']);
        try{
            $text=$extractor->extract(Storage::disk('local')->path($u->path),pathinfo($u->original_name,PATHINFO_EXTENSION));
            $parsed=['professional_summary'=>null,'experiences'=>[],'education'=>[],'skills'=>[]];
            try{
                $r=$ai->generate('resume_parsing',
                    'Extract resume data and return valid JSON only with keys title, professional_summary, experiences, education, skills, certifications, projects, languages. Never invent facts.',
                    $text,$u->user_id);
                $clean=preg_replace('/```(?:json)?|```/','',$r['text']);
                $parsed=json_decode(trim($clean),true,512,JSON_THROW_ON_ERROR);
            }catch(\Throwable){}
            $u->update(['status'=>'ready','extracted_text'=>$text,'parsed_data'=>$parsed,'processed_at'=>now()]);
        }catch(\Throwable $e){
            report($e); $u->update(['status'=>'failed','parsing_error'=>'Document analysis failed. Please try again or enter your details manually.']);
        }
    }
}
