<?php

namespace App\Http\Controllers\Certificates;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateRecommendation;
use App\Services\CertificatePdfService;
use App\Services\CertificateRecommendationService;
use App\Services\ParticipantCertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets instructors and administrators open participants' certificates in the
 * browser: issued (generated) certificates, and draft previews of certificates
 * that have only been recommended. Previews are never saved or issued.
 */
class StaffCertificateController extends Controller
{
    public function __construct(
        private readonly CertificateRecommendationService $access,
        private readonly ParticipantCertificateService $certificates,
    ) {
    }

    /** An issued course ("course") or event ("event") certificate, shown inline. */
    public function show(Request $request, string $type, int $id)
    {
        $certificate = $this->certificates->find($type, $id);

        $certificate->loadMissing('event');

        abort_unless($this->access->canViewCertificate(
            $request->user(),
            $certificate instanceof Certificate ? $certificate->course_id : null,
            $certificate->event
        ), 403);

        return $this->certificates->response($certificate, preview: true);
    }

    /**
     * The certificate for a recommendation: the issued one once approved,
     * otherwise a draft preview of what would be issued.
     */
    public function recommendation(Request $request, CertificateRecommendation $recommendation, CertificatePdfService $pdf)
    {
        $recommendation->loadMissing(['user', 'course', 'event', 'certificate', 'eventCertificate']);

        abort_unless($this->access->canViewCertificate($request->user(), $recommendation->course_id, $recommendation->event), 403);

        if ($recommendation->certificate) {
            return $this->certificates->response($recommendation->certificate, preview: true);
        }

        if ($recommendation->eventCertificate) {
            return $this->certificates->response($recommendation->eventCertificate, preview: true);
        }

        abort_unless($recommendation->user && ($recommendation->course || $recommendation->event), 404);

        $bytes = $recommendation->context_type === 'event'
            ? $this->certificates->previewEvent($recommendation->user, $recommendation->event)
            : $pdf->preview($recommendation->user, $recommendation->course);

        $name = 'certificate-preview-'.Str::slug($recommendation->user->name ?: 'participant').'.pdf';

        return response($bytes, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$name.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
