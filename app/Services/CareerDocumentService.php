<?php

namespace App\Services;

use App\Models\CoverLetter;
use App\Models\Resume;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class CareerDocumentService
{
    public function deleteResume(Resume $resume): void
    {
        $paths = $resume->portfolioFiles()->pluck('path');
        \Illuminate\Support\Facades\DB::transaction(function () use ($resume, $paths) {
            $resume->delete();
            \Illuminate\Support\Facades\DB::afterCommit(function () use ($paths) {
                foreach ($paths as $path) Storage::disk('local')->delete($path);
            });
        });
    }

    public function download(Resume|CoverLetter $document)
    {
        if ($document instanceof Resume) {
            $path = app(ResumePdfService::class)->render($document);
        } else {
            $document->load('user');
            $path = "cover-letters/{$document->user_id}/letter-{$document->id}.pdf";
            Storage::disk('local')->put($path, Pdf::loadView('career.cover-letter.pdf', ['letter' => $document])->output());
        }

        $filename = trim(preg_replace('/[^A-Za-z0-9_-]+/', '_', $document->title), '_') ?: 'Document';

        return Storage::disk('local')->download($path, $filename.'.pdf', [
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    public function share(Resume|CoverLetter $document): array
    {
        $expires = now()->addDays(7);

        return [
            'url' => URL::temporarySignedRoute('career.documents.shared', $expires, [
                'type' => $document instanceof Resume ? 'resume' : 'cover-letter',
                'id' => $document->id,
            ]),
            'expires_at' => $expires->toIso8601String(),
        ];
    }
}
