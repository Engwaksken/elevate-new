<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Enrolment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class CertificatePdfService
{
    public function render(Certificate $certificate): string
    {
        $certificate->load(['user.profile','course','event','template']);

        // Always use the current template for the course so design changes and course lists apply.
        $template = CertificateTemplate::resolveFor($certificate->course_id, $certificate->event_id);

        $details = $this->details($certificate);

        $html = view('certificates.pdf', compact('certificate', 'template', 'details'))->render();

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

    /**
     * What is printed on the certificate: the participant's full name, the course,
     * their cohort and the training period.
     *
     * @return array{full_name: string, title: string, cohort: ?string, period: ?string, issued_on: string, number: string, participant_code: ?string, verify_url: ?string}
     */
    public function details(Certificate $certificate): array
    {
        $certificate->loadMissing(['user.profile','course','event']);

        $enrolment = $certificate->course_id
            ? Enrolment::with('cohort')->where('course_id', $certificate->course_id)->where('user_id', $certificate->user_id)->first()
            : null;

        $cohort = $enrolment?->cohort;

        // Period: the cohort's dates, else the course's dates, else the participant's own enrolment dates.
        [$start, $end] = match (true) {
            (bool) ($cohort?->start_date || $cohort?->end_date) => [$cohort->start_date, $cohort->end_date],
            (bool) ($certificate->course?->start_date || $certificate->course?->end_date) => [$certificate->course->start_date, $certificate->course->end_date],
            default => [$enrolment?->enrolled_at ?? $enrolment?->created_at, $enrolment?->completed_at],
        };

        return [
            'full_name' => $certificate->user?->fullName() ?? '',
            'title' => $certificate->course?->title ?? $certificate->event?->title ?? 'Programme Activity',
            'cohort' => $cohort?->name,
            'period' => $this->period($start, $end),
            'issued_on' => ($certificate->issued_on ?? now())->format('d F Y'),
            'number' => (string) $certificate->certificate_number,
            'participant_code' => $certificate->user?->participant_code,
            'verify_url' => $certificate->verification_token ? route('certificates.verify', $certificate->verification_token) : null,
        ];
    }

    private function period(?Carbon $start, ?Carbon $end): ?string
    {
        if (! $start && ! $end) {
            return null;
        }

        if ($start && $end) {
            $startFormat = $start->year === $end->year ? 'd F' : 'd F Y';

            return $start->isSameDay($end)
                ? $end->format('d F Y')
                : $start->format($startFormat).' – '.$end->format('d F Y');
        }

        return $start ? 'From '.$start->format('d F Y') : 'Completed '.$end->format('d F Y');
    }
}
