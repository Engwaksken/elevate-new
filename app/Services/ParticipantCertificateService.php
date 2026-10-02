<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Enrolment;
use App\Models\EventCertificate;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ParticipantCertificateService
{
    public function list(User $user, ?int $courseId = null): Collection
    {
        $courses = Certificate::with(['course','event'])->where('user_id',$user->id)
            ->when($courseId, fn ($q) => $q->where('course_id',$courseId))->get();
        $events = EventCertificate::with('event')->where('user_id',$user->id)
            ->when($courseId, fn ($q) => $q->whereHas('event', fn ($e) => $e->where('course_id',$courseId)))->get();
        return $courses->map(fn ($certificate) => $this->present($certificate, 'course'))
            ->concat($events->map(fn ($certificate) => $this->present($certificate, 'event')))
            ->sortByDesc('issued_at')->values();
    }

    public function find(string $type, int $id): Certificate|EventCertificate
    {
        abort_unless(in_array($type, ['course','event'], true), 404);
        return $type === 'course' ? Certificate::findOrFail($id) : EventCertificate::findOrFail($id);
    }

    public function present(Certificate|EventCertificate $certificate, string $type): array
    {
        $course = $certificate instanceof Certificate;
        return ['id'=>$certificate->id,'type'=>$type,'course_id'=>$course ? $certificate->course_id : $certificate->event?->course_id,
            'title'=>$course ? ($certificate->course?->title ?? $certificate->event?->title ?? 'Course certificate') : ($certificate->event?->title ?? 'Event certificate'),
            'number'=>$course ? $certificate->certificate_number : $certificate->certificate_code,
            'issued_at'=>($course ? $certificate->issued_on : $certificate->issued_at)?->toIso8601String(),
            'preview_path'=>"/certificates/{$type}/{$certificate->id}/preview",
            'download_path'=>"/certificates/{$type}/{$certificate->id}/download",
            'share_path'=>"/certificates/{$type}/{$certificate->id}/share"];
    }

    public function response(Certificate|EventCertificate $certificate, bool $preview = false)
    {
        if ($certificate instanceof Certificate) {
            if (! $certificate->pdf_path || ! Storage::disk('local')->exists($certificate->pdf_path)) app(CertificatePdfService::class)->render($certificate);
            $path = $certificate->pdf_path;
            $number = $certificate->certificate_number;
        } else {
            $certificate->load(['event','user']);
            abort_unless($certificate->event && $certificate->user, 404);
            $event = $certificate->event; $user = $certificate->user;
            $template = CertificateTemplate::resolveFor(null, $event->id);
            $participantId = $event->course_id ? Enrolment::where('course_id',$event->course_id)->where('user_id',$user->id)->value('enrolment_code') : null;
            $participantId ??= $user->participant_code;
            $pdf = Pdf::loadView('events.certificates.pdf', compact('event','user','certificate','template','participantId'))->setPaper('a4', $template?->orientation ?: 'landscape');
            $path = 'certificates/events/'.$certificate->id.'.pdf';
            Storage::disk('local')->put($path, $pdf->output());
            $number = $certificate->certificate_code;
        }
        $name = 'certificate-'.preg_replace('/[^A-Za-z0-9_-]/','_', $number).'.pdf';
        $headers = ['Content-Type'=>'application/pdf','Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff','X-Robots-Tag'=>'noindex, nofollow'];
        return $preview ? Storage::disk('local')->response($path, $name, $headers, 'inline') : Storage::disk('local')->download($path, $name, $headers);
    }

    public function share(string $type, int $id): array
    {
        $expires = now()->addDays(7);
        return ['url'=>URL::temporarySignedRoute('certificates.shared', $expires, ['type'=>$type,'id'=>$id]), 'expires_at'=>$expires->toIso8601String()];
    }
}
