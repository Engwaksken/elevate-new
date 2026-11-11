<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class CertificatePdfService
{
    /** Certificate number shown on draft previews. */
    public const PREVIEW_NUMBER = 'DRAFT PREVIEW';

    public function render(Certificate $certificate): string
    {
        [$pdf, $template] = $this->build($certificate);

        $path = 'certificates/'.$certificate->certificate_number.'.pdf';

        Storage::disk('local')->put($path, $pdf->output());

        $certificate->update([
            'pdf_path' => $path,
            'certificate_template_id' => $template?->id,
            'period_start' => $certificate->period_start,
            'period_end' => $certificate->period_end,
        ]);

        return $path;
    }

    /**
     * PDF bytes of what a participant's course certificate would look like,
     * for staff reviewing a recommendation. Nothing is saved or issued.
     */
    public function preview(User $user, Course $course): string
    {
        $certificate = new Certificate([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'certificate_number' => self::PREVIEW_NUMBER,
            'issued_on' => now(),
        ]);
        $certificate->setRelation('user', $user)->setRelation('course', $course)
            ->setRelation('event', null)->setRelation('template', null);

        return $this->build($certificate)[0]->output();
    }

    /**
     * @return array{0: \Barryvdh\DomPDF\PDF, 1: ?CertificateTemplate}
     */
    private function build(Certificate $certificate): array
    {
        $certificate->loadMissing(['user','course','event','template']);

        $template = $certificate->template;

        $template ??= CertificateTemplate::resolveFor($certificate->course_id, $certificate->event_id);

        [$participantId, $periodStart, $periodEnd] = $this->resolveMeta($certificate);

        if (! $certificate->period_start && $periodStart) {
            $certificate->period_start = $periodStart;
        }
        if (! $certificate->period_end && $periodEnd) {
            $certificate->period_end = $periodEnd;
        }

        $layout = $template?->fieldLayout() ?? CertificateTemplate::defaultLayout();

        $fields = [
            'heading' => 'Certificate of Completion',
            'name' => $certificate->user?->name ?? '',
            'course' => $certificate->course?->title ?? $certificate->event?->title ?? 'Programme Activity',
            'certificate_number' => $certificate->certificate_number,
            'participant_id' => $participantId ?? '',
            'start_period' => $certificate->period_start ? $certificate->period_start->format('d M Y') : '',
            'end_period' => $certificate->period_end ? $certificate->period_end->format('d M Y') : '',
            'issued' => ($certificate->issued_on ?? now())->format('d M Y'),
        ];

        $html = view('certificates.pdf', compact('certificate', 'template', 'participantId', 'layout', 'fields'))->render();

        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            throw new \RuntimeException('DOMPDF package is not installed.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('a4', $template?->orientation ?: 'landscape');

        return [$pdf, $template];
    }

    /**
     * @return array{0: ?string, 1: ?\Carbon\CarbonInterface, 2: ?\Carbon\CarbonInterface}
     */
    public function resolveMeta(Certificate $certificate): array
    {
        $enrolment = null;
        if ($certificate->course_id) {
            $enrolment = Enrolment::where('user_id', $certificate->user_id)
                ->where('course_id', $certificate->course_id)
                ->first();
        }

        $participantId = $enrolment?->enrolment_code ?? $certificate->user?->participant_code;

        $periodStart = $certificate->period_start ?? $enrolment?->started_at ?? $certificate->course?->start_date;
        $periodEnd = $certificate->period_end ?? $enrolment?->completed_at ?? $certificate->course?->end_date;

        return [$participantId, $periodStart, $periodEnd];
    }
}
