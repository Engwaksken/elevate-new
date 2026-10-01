<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateRecommendation;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Event;
use App\Models\EventAttendanceRecord;
use App\Models\EventCertificate;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Certificate recommendations: staff recommend participants, administrators approve
 * (which issues the certificate) or reject, and everyone can track the outcome.
 */
class CertificateRecommendationService
{
    public const APPROVER_ROLES = ['administrator', 'super-administrator', 'super-admin'];

    public const RECOMMENDER_ROLES = ['instructor', 'trainer', 'programs-lead', 'program-manager', 'program-officer'];

    private const PROGRAMME_ROLES = ['programs-lead', 'program-manager', 'program-officer'];

    public function __construct(
        private UserNotificationService $notifications,
        private CertificatePdfService $pdf,
    ) {
    }

    public function canApprove(User $user): bool
    {
        return $user->isActive() && $user->isSuperAdmin();
    }

    public function canRecommend(User $user): bool
    {
        return $this->canApprove($user) || ($user->isActive() && $user->hasAnyRole(self::RECOMMENDER_ROLES));
    }

    /**
     * Courses the user may recommend for: everything for approvers and programme staff,
     * only assigned courses for instructors and trainers.
     */
    public function coursesFor(User $user): Builder
    {
        $query = Course::query()->orderBy('title');

        if ($this->hasProgrammeWideAccess($user)) {
            return $query;
        }

        return $query->whereIn('id', $user->instructedCourses()->pluck('courses.id'));
    }

    /**
     * Events the user may recommend for; instructors see events linked to their courses.
     */
    public function eventsFor(User $user): Builder
    {
        $query = Event::query()->latest('starts_at');

        if ($this->hasProgrammeWideAccess($user)) {
            return $query;
        }

        return $query->whereIn('course_id', $user->instructedCourses()->pluck('courses.id'));
    }

    /**
     * Recommendations the user can see: all for approvers and programme staff, else their own.
     */
    public function visibleTo(User $user): Builder
    {
        $query = CertificateRecommendation::query();

        if ($this->hasProgrammeWideAccess($user)) {
            return $query;
        }

        return $query->where('recommended_by', $user->id);
    }

    /**
     * Participants of a course or event with their certificate and recommendation status.
     *
     * @return Collection<int, array{user: User, detail: string, certificate: bool, recommendation: ?CertificateRecommendation}>
     */
    public function participants(Course|Event $target): Collection
    {
        if ($target instanceof Course) {
            $rows = Enrolment::with('user')->where('course_id', $target->id)->get()
                ->filter(fn (Enrolment $enrolment) => $enrolment->user)
                ->map(fn (Enrolment $enrolment) => [
                    'user' => $enrolment->user,
                    'detail' => ucfirst((string) $enrolment->status).' · '.round((float) $enrolment->progress_percent).'% progress'
                        .($enrolment->final_score !== null ? ' · score '.round((float) $enrolment->final_score, 1) : ''),
                ]);
            $issued = Certificate::where('course_id', $target->id)->pluck('user_id');
        } else {
            $attendance = EventAttendanceRecord::where('event_id', $target->id)->pluck('attendance_status', 'user_id');
            $rows = EventRegistration::with('user')->where('event_id', $target->id)->get()
                ->filter(fn (EventRegistration $registration) => $registration->user)
                ->map(fn (EventRegistration $registration) => [
                    'user' => $registration->user,
                    'detail' => 'Registration '.Str::lower((string) $registration->status)
                        .' · attendance '.Str::lower(str_replace('_', ' ', (string) ($attendance[$registration->user_id] ?? 'not recorded'))),
                ]);
            $issued = EventCertificate::where('event_id', $target->id)->whereNotNull('issued_by')->pluck('user_id');
        }

        $latest = CertificateRecommendation::query()
            ->where($target instanceof Course ? 'course_id' : 'event_id', $target->id)
            ->latest('id')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        $issued = $issued->flip();

        return $rows
            ->unique(fn (array $row) => $row['user']->id)
            ->map(fn (array $row) => $row + [
                'certificate' => $issued->has($row['user']->id),
                'recommendation' => $latest->get($row['user']->id),
            ])
            ->sortBy(fn (array $row) => Str::lower($row['user']->name))
            ->values();
    }

    /**
     * Recommend participants. Anyone already certified or with a pending recommendation is skipped.
     *
     * @return array{created: int, skipped: int}
     */
    public function recommend(User $recommender, Course|Event $target, array $userIds, ?string $reason): array
    {
        $eligible = $this->eligibleUserIds($target, $userIds);
        $created = collect();

        DB::transaction(function () use ($recommender, $target, $eligible, $reason, &$created) {
            foreach ($eligible as $userId) {
                $created->push(CertificateRecommendation::create($this->contextAttributes($target) + [
                    'user_id' => $userId,
                    'recommended_by' => $recommender->id,
                    'reason' => $reason,
                    'status' => 'pending',
                ]));
            }
        });

        if ($created->isNotEmpty()) {
            $this->notifications->sendToManySafely(
                $this->approvers(),
                'certificate_recommendation',
                'Certificate recommendations awaiting review',
                $recommender->name.' recommended '.$created->count().' '.Str::plural('participant', $created->count())
                    .' for a certificate in '.$target->title.'.',
                route('certificates.recommendations.index', ['status' => 'pending'])
            );
        }

        return ['created' => $created->count(), 'skipped' => count(array_unique($userIds)) - $created->count()];
    }

    /**
     * Issue certificates straight away (administrators), recording an approved recommendation for tracking.
     *
     * @return array{created: int, skipped: int}
     */
    public function issueDirectly(User $admin, Course|Event $target, array $userIds, ?string $reason): array
    {
        $eligible = $this->eligibleUserIds($target, $userIds, includePending: true);

        foreach ($eligible as $userId) {
            $recommendation = CertificateRecommendation::query()
                ->where($this->contextAttributes($target))
                ->where('user_id', $userId)
                ->pending()
                ->first()
                ?? CertificateRecommendation::create($this->contextAttributes($target) + [
                    'user_id' => $userId,
                    'recommended_by' => $admin->id,
                    'reason' => $reason,
                    'status' => 'pending',
                ]);

            $this->approve($recommendation, $admin, $reason);
        }

        return ['created' => count($eligible), 'skipped' => count(array_unique($userIds)) - count($eligible)];
    }

    public function approve(CertificateRecommendation $recommendation, User $reviewer, ?string $notes = null): void
    {
        if (! $recommendation->isPending()) {
            return;
        }

        $recommendation->loadMissing(['user', 'course', 'event']);

        DB::transaction(function () use ($recommendation, $reviewer, $notes) {
            $issued = $recommendation->context_type === 'event'
                ? ['event_certificate_id' => $this->issueEventCertificate($recommendation->event, $recommendation->user, $reviewer)->id]
                : ['certificate_id' => $this->issueCourseCertificate($recommendation->course, $recommendation->user)->id];

            $recommendation->update($issued + [
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ]);
        });

        $title = $recommendation->contextTitle();
        $url = $recommendation->context_type === 'event'
            ? route('events.certificate', $recommendation->event_id)
            : route('certificates.mine');

        $this->notifySafely($recommendation->user, 'certificate_issued', 'Your certificate is ready',
            'Your certificate for '.$title.' has been issued and is ready to download.', $url);

        $this->notifyRecommender($recommendation, $reviewer, 'Certificate recommendation approved',
            'Your recommendation for '.$recommendation->user->name.' ('.$title.') was approved and the certificate issued.');
    }

    public function reject(CertificateRecommendation $recommendation, User $reviewer, ?string $notes = null): void
    {
        if (! $recommendation->isPending()) {
            return;
        }

        $recommendation->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        $recommendation->loadMissing(['user', 'course', 'event']);

        $this->notifyRecommender($recommendation, $reviewer, 'Certificate recommendation not approved',
            'Your recommendation for '.$recommendation->user?->name.' ('.$recommendation->contextTitle().') was not approved.'
                .($notes ? ' Reason: '.$notes : ''));
    }

    private function issueCourseCertificate(Course $course, User $user): Certificate
    {
        $certificate = Certificate::firstOrCreate(
            ['course_id' => $course->id, 'user_id' => $user->id],
            [
                'certificate_number' => 'EH360-'.now()->format('Y').'-'.strtoupper(Str::random(8)),
                'issued_on' => now()->toDateString(),
                'verification_token' => Str::uuid()->toString(),
            ]
        );

        // The PDF can be regenerated on download; a rendering problem must not block issuing.
        try {
            $this->pdf->render($certificate);
        } catch (\Throwable $e) {
            report($e);
        }

        return $certificate;
    }

    private function issueEventCertificate(Event $event, User $user, User $issuer): EventCertificate
    {
        $certificate = EventCertificate::firstOrNew(['event_id' => $event->id, 'user_id' => $user->id]);

        $certificate->fill([
            'event_attendance_record_id' => $certificate->event_attendance_record_id
                ?? EventAttendanceRecord::where('event_id', $event->id)->where('user_id', $user->id)->value('id'),
            'certificate_code' => $certificate->certificate_code ?: (string) Str::uuid(),
            'issued_at' => $certificate->issued_at ?? now(),
            'issued_by' => $certificate->issued_by ?? $issuer->id,
        ])->save();

        return $certificate;
    }

    /**
     * Requested ids that belong to the course/event and are not yet certified or (optionally) pending.
     */
    private function eligibleUserIds(Course|Event $target, array $userIds, bool $includePending = false): array
    {
        $participants = $this->participants($target)->keyBy(fn (array $row) => $row['user']->id);

        return collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter(function (int $id) use ($participants, $includePending) {
                $row = $participants->get($id);

                return $row
                    && ! $row['certificate']
                    && ($includePending || ! $row['recommendation']?->isPending());
            })
            ->values()
            ->all();
    }

    private function contextAttributes(Course|Event $target): array
    {
        return $target instanceof Course
            ? ['context_type' => 'course', 'course_id' => $target->id, 'event_id' => null]
            : ['context_type' => 'event', 'course_id' => null, 'event_id' => $target->id];
    }

    private function hasProgrammeWideAccess(User $user): bool
    {
        return $this->canApprove($user) || $user->hasAnyRole(self::PROGRAMME_ROLES);
    }

    private function approvers(): Collection
    {
        return User::query()
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', self::APPROVER_ROLES))
            ->pluck('id');
    }

    private function notifyRecommender(CertificateRecommendation $recommendation, User $reviewer, string $title, string $message): void
    {
        $recommender = $recommendation->recommender;

        if ($recommender && $recommender->id !== $reviewer->id) {
            $this->notifySafely($recommender, 'certificate_recommendation', $title, $message,
                route('certificates.recommendations.index'));
        }
    }

    private function notifySafely(?User $user, string $type, string $title, string $message, string $url): void
    {
        if (! $user) {
            return;
        }

        try {
            $this->notifications->send($user, $type, $title, $message, $url);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
