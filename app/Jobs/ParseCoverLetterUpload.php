<?php

namespace App\Jobs;

use App\Models\CoverLetterUpload;
use App\Services\CareerAiService;
use App\Services\DocumentTextExtractor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ParseCoverLetterUpload implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $uploadId) {}

    public function handle(DocumentTextExtractor $extractor, CareerAiService $ai): void
    {
        $upload = CoverLetterUpload::findOrFail($this->uploadId);
        $upload->update(['status'=>'processing']);

        try {
            $text = $extractor->extract(
                Storage::disk('local')->path($upload->path),
                pathinfo($upload->original_name, PATHINFO_EXTENSION)
            );

            $parsed = ['body'=>$text];

            try {
                $result = $ai->generate(
                    'cover_letter_parsing',
                    'Extract this cover letter into JSON only with keys title, employer_name, job_title, recipient_name, body. Never invent missing details.',
                    $text,
                    $upload->user_id
                );

                $clean = preg_replace('/```(?:json)?|```/','',$result['text']);
                $parsed = json_decode(trim($clean),true,512,JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                // Keep plain extracted text if AI is unavailable.
            }

            $upload->update([
                'status'=>'ready',
                'extracted_text'=>$text,
                'parsed_data'=>$parsed,
                'processed_at'=>now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            $upload->update([
                'status'=>'failed',
                'parsing_error'=>'Cover letter analysis failed. Please retry or create the cover letter manually.',
            ]);
        }
    }
}
