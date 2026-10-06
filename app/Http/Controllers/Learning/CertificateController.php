<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Certificate;
use App\Models\EventCertificate;
use App\Services\CertificatePdfService;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    use ExportsTables;
    public function index(Request $request)
    {
        if ($format = $this->exportFormat($request)) {
            $rows = Certificate::with(['course','event'])
                ->where('user_id', auth()->id())
                ->latest('issued_on')
                ->get()
                ->map(fn ($c) => [
                    'type' => $c->event_id && ! $c->course_id ? 'Event' : 'Course',
                    'title' => $c->course?->title ?? $c->event?->title ?? 'Certificate',
                    'number' => $c->certificate_number,
                    'issued' => $c->issued_on,
                ])
                ->concat(EventCertificate::with('event')
                    ->where('user_id', auth()->id())
                    ->latest('issued_at')
                    ->get()
                    ->map(fn ($c) => [
                        'type' => 'Event',
                        'title' => $c->event?->title ?? 'Event certificate',
                        'number' => $c->certificate_code,
                        'issued' => $c->issued_at,
                    ]));

            return $this->exportTable($format, 'My Certificates', $rows, [
                'Type' => 'type',
                'Course / Event' => 'title',
                'Certificate number' => 'number',
                'Issued' => 'issued',
            ], []);
        }

        return view('learning.certificates', [
            'certificates' => Certificate::with(['course','event'])
                ->where('user_id', auth()->id())
                ->latest('issued_on')
                ->paginate(20),
            'eventCertificates' => EventCertificate::with('event')
                ->where('user_id', auth()->id())
                ->latest('issued_at')
                ->get(),
        ]);
    }

    public function download(Certificate $certificate, CertificatePdfService $service)
    {
        abort_unless((int) $certificate->user_id === (int) auth()->id(), 403);

        return app(\App\Services\ParticipantCertificateService::class)->response($certificate);
    }

    public function verify(string $token)
    {
        $certificate = Certificate::with(['user','course','event'])
            ->where('verification_token', $token)
            ->first();

        return view('certificates.verify', compact('certificate'));
    }
}
