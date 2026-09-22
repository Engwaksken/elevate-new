<?php
namespace App\Services;

use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;

class CertificatePdfService
{
    public function render(Certificate $certificate): string
    {
        // Requires barryvdh/laravel-dompdf (or equivalent PDF package).
        // QR rendering can use simplesoftwareio/simple-qrcode if approved.
        $certificate->load(['user','course']);

        $html = view('certificates.pdf', compact('certificate'))->render();

        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            throw new \RuntimeException('DOMPDF package is not installed.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4','landscape');

        $path = 'certificates/'.$certificate->certificate_number.'.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        $certificate->update(['pdf_path'=>$path]);

        return $path;
    }
}
