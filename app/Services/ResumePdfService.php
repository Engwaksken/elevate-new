<?php
namespace App\Services;

use App\Models\Resume;
use Illuminate\Support\Facades\Storage;

class ResumePdfService
{
    public function render(Resume $resume): string
    {
        $resume->load(['user.profile','experiences','education','skills','certifications','languages','projects']);

        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            throw new \RuntimeException('DOMPDF package is not installed.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('career.resume.pdf', compact('resume'));
        $path = "resumes/{$resume->user_id}/resume-{$resume->id}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }
}
