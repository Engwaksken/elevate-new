<?php

namespace App\Services\Participant;

use App\Exceptions\ParticipantRuleException;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssignmentExtensionRequest;
use App\Models\User;
use App\Services\Learning\LearningFileService;
use App\Services\UserNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Participant-facing assignment rules: effective due dates (with approved extensions),
 * overdue blocking with an offline grace window, submissions and extension requests.
 */
class ParticipantAssignmentService
{
    /** A queued offline submission made before the deadline is accepted if it reaches the server within this window. */
    public const OFFLINE_GRACE_HOURS = 72;

    /** Extensions may be requested once the assignment is overdue or due within this many hours. */
    public const EXTENSION_WINDOW_HOURS = 48;

    public const OVERDUE_MESSAGE = 'This assignment is past its due date. Request an extension from your instructor.';

    public function __construct(
        private readonly ParticipantLessonService $lessons,
        private readonly UserNotificationService $notifications,
        private readonly LearningFileService $files
    ) {
    }

    /**
     * Batch-load per-participant data for many assessments (no N+1).
     *
     * @return array{attempts: array<int,int>, latest_attempts: array<int,AssessmentAttempt>, latest: array<int,AssignmentExtensionRequest>, approved: array<int,AssignmentExtensionRequest>}
     */
    public function context(User $user, iterable $assessmentIds): array
    {
        $ids = collect($assessmentIds)->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            return ['attempts' => [], 'latest_attempts' => [], 'latest' => [], 'approved' => []];
        }

        $attemptRows = AssessmentAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('assessment_id', $ids)
            ->orderByDesc('attempt_number')
            ->orderByDesc('id')
            ->get()
            ->groupBy('assessment_id');

        $attempts = [];
        $latestAttempts = [];

        foreach ($attemptRows as $assessmentId => $rows) {
            $attempts[(int) $assessmentId] = $rows->count();
            $latestAttempts[(int) $assessmentId] = $rows->first();
        }

        $requests = AssignmentExtensionRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('assessment_id', $ids)
            ->orderByDesc('id')
            ->get()
            ->groupBy('assessment_id');

        $latest = [];
        $approved = [];

        foreach ($requests as $assessmentId => $rows) {
            $latest[(int) $assessmentId] = $rows->first();
            $approvedRow = $rows->firstWhere('status', AssignmentExtensionRequest::STATUS_APPROVED);
            if ($approvedRow) {
                $approved[(int) $assessmentId] = $approvedRow;
            }
        }

        return [
            'attempts' => $attempts,
            'latest_attempts' => $latestAttempts,
            'latest' => $latest,
            'approved' => $approved,
        ];
    }

    public function effectiveDueAt(Assessment $assessment, array $context): ?Carbon
    {
        $approved = $context['approved'][$assessment->id] ?? null;

        if ($approved && $approved->approved_due_at) {
            return $approved->approved_due_at->copy();
        }

        return $assessment->due_at?->copy();
    }

    /**
     * Participant-specific fields for one assessment.
     */
    public function state(Assessment $assessment, array $context): array
    {
        $due = $this->effectiveDueAt($assessment, $context);
        $count = (int) ($context['attempts'][$assessment->id] ?? 0);
        $maxAttempts = max(1, (int) $assessment->max_attempts);
        $latest = $context['latest'][$assessment->id] ?? null;
        $isOverdue = $due !== null && $due->isPast();
        $attemptsLeft = max(0, $maxAttempts - $count);
        $attempt = $context['latest_attempts'][$assessment->id] ?? null;
        $isGraded = $attempt !== null && ($attempt->status === 'graded' || $attempt->graded_at !== null);

        return [
            'is_graded' => $isGraded,
            'latest_submission' => $attempt ? [
                'id' => $attempt->id,
                'attempt_number' => (int) $attempt->attempt_number,
                'status' => $attempt->status,
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'graded_at' => $attempt->graded_at?->toIso8601String(),
                'score' => $isGraded && $attempt->score !== null ? (float) $attempt->score : null,
                'percentage' => $isGraded && $attempt->percentage !== null ? (float) $attempt->percentage : null,
                'instructor_feedback' => $isGraded ? $attempt->getAttribute('instructor_feedback') : null,
                'files' => $this->lessons->presentAttemptFiles($attempt),
            ] : null,
            'effective_due_at' => $due?->toIso8601String(),
            'is_overdue' => $isOverdue,
            'can_submit' => (bool) $assessment->is_published && ! $isOverdue && $attemptsLeft > 0,
            'submissions_count' => $count,
            'attempts_remaining' => $attemptsLeft,
            'extension_request' => $latest?->toApiArray(),
            'can_request_extension' => (bool) $assessment->is_published
                && ! ($latest && $latest->isPending())
                && $attemptsLeft > 0
                && $due !== null
                && $due->lte(now()->addHours(self::EXTENSION_WINDOW_HOURS)),
        ];
    }

    /**
     * Assessment payload for the mobile app (presentAssessment + participant state).
     */
    public function present(Assessment $assessment, array $context): array
    {
        return $this->lessons->presentAssessment($assessment) + $this->state($assessment, $context);
    }

    public function presentMany(Collection $assessments, User $user): Collection
    {
        $context = $this->context($user, $assessments->pluck('id'));

        return $assessments->map(fn (Assessment $assessment) => $this->present($assessment, $context))->values();
    }

    /**
     * Create a submission (shared by POST /assignments/{id}/submit and the offline-actions queue).
     * $file accepts one upload or a list of uploads (multi-file submissions).
     *
     * @param  UploadedFile|array<int,UploadedFile>|null  $file
     * @return array{attempt: AssessmentAttempt, duplicate: bool}
     */
    public function submit(
        User $user,
        Assessment $assessment,
        ?string $text,
        UploadedFile|array|null $file,
        ?string $clientSubmissionId,
        ?Carbon $clientCreatedAt = null
    ): array {
        if ($clientSubmissionId) {
            $duplicate = AssessmentAttempt::where('client_submission_id', $clientSubmissionId)
                ->where('user_id', $user->id)
                ->first();

            if ($duplicate) {
                return ['attempt' => $duplicate, 'duplicate' => true];
            }
        }

        $context = $this->context($user, [$assessment->id]);
        $existingAttempts = (int) ($context['attempts'][$assessment->id] ?? 0);

        if ($existingAttempts >= (int) $assessment->max_attempts) {
            throw new ParticipantRuleException('Maximum attempts reached.', 'max_attempts_reached');
        }

        $this->ensureNotOverdue($assessment, $context, $clientSubmissionId ? $clientCreatedAt : null);

        $uploads = array_values(array_filter(
            $file instanceof UploadedFile ? [$file] : (array) $file,
            fn ($upload) => $upload instanceof UploadedFile
        ));

        $attempt = AssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'attempt_number' => $existingAttempts + 1,
            'client_submission_id' => $clientSubmissionId,
            'submission_text' => $text,
            'status' => 'submitted',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->files->storeSubmissionFiles($attempt, $uploads);
        $attempt->load('files');

        return ['attempt' => $attempt, 'duplicate' => false];
    }

    /**
     * Block submissions after the effective due date, except queued offline submissions that
     * were made before it and arrive within the grace window.
     */
    public function ensureNotOverdue(Assessment $assessment, array $context, ?Carbon $clientCreatedAt): void
    {
        $due = $this->effectiveDueAt($assessment, $context);

        if ($due === null || ! $due->isPast()) {
            return;
        }

        if ($clientCreatedAt !== null
            && $clientCreatedAt->lte($due)
            && $clientCreatedAt->lte(now()->addMinutes(5))
            && $clientCreatedAt->gte(now()->subHours(self::OFFLINE_GRACE_HOURS))
        ) {
            return;
        }

        throw new ParticipantRuleException(self::OVERDUE_MESSAGE, 'overdue');
    }

    public function createExtensionRequest(User $user, Assessment $assessment, string $reason, ?Carbon $requestedDueAt): AssignmentExtensionRequest
    {
        $context = $this->context($user, [$assessment->id]);
        $latest = $context['latest'][$assessment->id] ?? null;

        if ($latest && $latest->isPending()) {
            throw new ParticipantRuleException('You already have a pending extension request for this assignment.', 'extension_pending');
        }

        if ((int) ($context['attempts'][$assessment->id] ?? 0) >= (int) $assessment->max_attempts) {
            throw new ParticipantRuleException('Maximum attempts reached.', 'max_attempts_reached');
        }

        $due = $this->effectiveDueAt($assessment, $context);

        if ($due === null || $due->gt(now()->addHours(self::EXTENSION_WINDOW_HOURS))) {
            throw new ParticipantRuleException(
                'Extensions can only be requested for assignments that are overdue or due within 48 hours.',
                'extension_not_needed'
            );
        }

        $request = AssignmentExtensionRequest::create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'reason' => $reason,
            'requested_due_at' => $requestedDueAt,
            'status' => AssignmentExtensionRequest::STATUS_PENDING,
        ]);

        $assessment->loadMissing('course');
        $actionUrl = Route::has('instructor.courses.manage') && $assessment->course
            ? route('instructor.courses.manage', ['course' => $assessment->course, 'tab' => 'extensions'])
            : null;

        foreach ($assessment->course?->instructors()->get() ?? [] as $instructor) {
            $this->notifications->send(
                $instructor,
                'assignment_extension_requested',
                'Extension requested: '.$assessment->title,
                $user->name.' asked for more time on "'.$assessment->title.'".',
                $actionUrl,
                $this->notificationData($request, $assessment)
            );
        }

        return $request;
    }

    public function approve(AssignmentExtensionRequest $request, User $reviewer, Carbon $dueAt, ?string $note): AssignmentExtensionRequest
    {
        $request->update([
            'status' => AssignmentExtensionRequest::STATUS_APPROVED,
            'approved_due_at' => $dueAt,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'reviewer_note' => $note,
        ]);

        $assessment = $request->assessment()->first();
        $this->notifyParticipant(
            $request,
            $assessment,
            'assignment_extension_approved',
            'Extension approved: '.$assessment->title,
            'Your new due date is '.$dueAt->format('d M Y H:i').'.'.($note ? ' Note: '.$note : '')
        );

        return $request->refresh();
    }

    public function reject(AssignmentExtensionRequest $request, User $reviewer, ?string $note): AssignmentExtensionRequest
    {
        $request->update([
            'status' => AssignmentExtensionRequest::STATUS_REJECTED,
            'approved_due_at' => null,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'reviewer_note' => $note,
        ]);

        $assessment = $request->assessment()->first();
        $this->notifyParticipant(
            $request,
            $assessment,
            'assignment_extension_rejected',
            'Extension not approved: '.$assessment->title,
            'Your extension request was not approved.'.($note ? ' Note: '.$note : '')
        );

        return $request->refresh();
    }

    private function notifyParticipant(AssignmentExtensionRequest $request, Assessment $assessment, string $type, string $title, string $message): void
    {
        $participant = $request->user()->first();

        if ($participant) {
            $this->notifications->send($participant, $type, $title, $message, null, $this->notificationData($request, $assessment));
        }
    }

    private function notificationData(AssignmentExtensionRequest $request, Assessment $assessment): array
    {
        return [
            'assessment_id' => $assessment->id,
            'course_id' => $assessment->course_id,
            'extension_request_id' => $request->id,
            'approved_due_at' => $request->approved_due_at?->toIso8601String(),
        ];
    }
}
