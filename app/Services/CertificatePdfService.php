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

        if (! $template) {
            $template = CertificateTemplate::query()
                ->where('is_active', true)
                ->where(function ($query) use ($certificate) {
                    if ($certificate->course_id) {
                        $query->orWhere(function ($q) use ($certificate) {
                            $q->where('context_type', 'course')
                                ->where('course_id', $certificate->course_id);
                        });
                    }

                    if ($certificate->event_id) {
                        $query->orWhere(function ($q) use ($certificate) {
                            $q->where('context_type', 'event')
                                ->where('event_id', $certificate->event_id);
                        });
                    }

                    $query->orWhere('context_type', 'default');
                })
                ->latest()
                ->first();
        }

        $html = view('certificates.pdf', compact('certificate', 'template'))->render();

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