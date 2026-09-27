<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Services\CertificatePdfService;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function index()
    {
        return view('learning.certificates', [
            'certificates' => Certificate::with(['course','event'])
                ->where('user_id', auth()->id())
                ->latest('issued_on')
                ->paginate(20),
        ]);
    }

    public function download(Certificate $certificate, CertificatePdfService $service)
    {
        abort_unless((int) $certificate->user_id === (int) auth()->id(), 403);

        if (! $certificate->pdf_path || ! Storage::disk('local')->exists($certificate->pdf_path)) {
            $service->render($certificate);
            $certificate->refresh();
        }

        return Storage::disk('local')->download(
            $certificate->pdf_path,
            'certificate-'.$certificate->certificate_number.'.pdf'
        );
    }

    public function verify(string $token)
    {
        $certificate = Certificate::with(['user','course','event'])
            ->where('verification_token', $token)
            ->first();

        return view('certificates.verify', compact('certificate'));
    }
}