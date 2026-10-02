<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use Illuminate\Support\Facades\Storage;

class CertificatePdfService
{
    public function render(Certificate $certificate): string
    {
        $certificate->load(['user','course','event','template']);

        $template = $certificate->template;

        $template ??= CertificateTemplate::resolveFor($certificate->course_id, $certificate->event_id);

        $participantId = $certificate->course_id
            ? \App\Models\Enrolment::where('user_id',$certificate->user_id)->where('course_id',$certificate->course_id)->value('enrolment_code')
            : null;
        $participantId ??= $certificate->user?->participant_code;
        $html = view('certificates.pdf', compact('certificate', 'template', 'participantId'))->render();

        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            throw new \RuntimeException('DOMPDF package is not installed.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('a4', $template?->orientation ?: 'landscape');

        $path = 'certificates/'.$certificate->certificate_number.'.pdf';

        Storage::disk('local')->put($path, $pdf->output());

        $certificate->update([
            'pdf_path' => $path,
            'certificate_template_id' => $template?->id,
        ]);

        return $path;
    }
}
