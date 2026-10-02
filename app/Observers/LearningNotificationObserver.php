<?php

namespace App\Observers;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\CourseAnnouncement;
use App\Models\CourseApplication;
use App\Models\Enrolment;
use App\Models\JobApplication;
use App\Models\Lesson;
use App\Models\MentorMatch;
use App\Models\MentorshipSession;
use App\Services\UserNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * In-app notifications for participant-facing events: enrolment, lessons,
 * assignments, grading, announcements, course applications, mentorship and
 * job applications.
 *
 * Runs after the surrounding transaction commits, so a rolled-back action never
 * notifies anyone, and a failure here is reported rather than thrown. The user
 * who performed the action is never notified about it.
 */
class LearningNotificationObserver implements ShouldHandleEventsAfterCommit
{
    /** Enrolment statuses that no longer receive course notifications. */
    public const INACTIVE_ENROLMENT_STATUSES = ['withdrawn', 'cancelled', 'failed'];

    private static bool $muted = false;

    /**
     * Run a callback without per-record notifications (e.g. a bulk import that
     * sends its own batched notifications afterwards).
     */
    public static function withoutNotifications(callable $callback): mixed
    {
        $previous = self::$muted;
        self::$muted = true;

        try {
            return $callback();
        } finally {
            self::$muted = $previous;
        }
    }

    public function __construct(private readonly UserNotificationService $notifications)
    {
    }

    public function created(Model $model): void
    {
        $this->handle($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->handle($model, 'updated');
    }

    private function handle(Model $model, string $event): void
    {
        if (self::$muted || ! Schema::hasTable('user_notifications')) {
            return;
        }

        try {
            match (true) {
                $model instanceof Enrolment => $this->enrolment($model, $event),
                $model instanceof Lesson => $this->lesson($model, $event),
                $model instanceof Assessment => $this->assessment($model, $event),
                $model instanceof AssessmentAttempt => $this->attempt($model, $event),
                $model instanceof CourseAnnouncement => $this->announcement($model, $event),
                $model instanceof CourseApplication => $this->courseApplication($model, $event),
                $model instanceof MentorMatch => $this->mentorMatch($model, $event),
                $model instanceof MentorshipSession => $this->mentorshipSession($model, $event),
                $model instanceof JobApplication => $this->jobApplication($model, $event),
                default => null,
            };
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /* ---------------------------------------------------------------- Enrolment */

    private function enrolment(Enrolment $enrolment, string $event): void
    {
        if ($event === 'updated' && ! $enrolment->wasChanged('status')) {
            return;
        }

        $content = self::enrolmentContent($enrolment, $event);

        if ($content) {
            $this->notifications->sendToMany([$enrolment->user_id], ...$content);
        }
    }

    /**
     * [type, title, message, url, data] for an enrolment event, or null when the
     * event is not worth a notification. Shared with the bulk enrolment import.
     */
    public static function enrolmentContent(Enrolment $enrolment, string $event): ?array
    {
        $course = $enrolment->course()->first();
        $title = $course?->title ?? 'a course';
        $url = self::route('learning.course.dashboard', $course) ?? self::route('learning.my-courses');
        $data = ['course_id' => $enrolment->course_id, 'enrolment_id' => $enrolment->id, 'status' => $enrolment->status, 'event' => $event];

        if ($event === 'created') {
            if (in_array($enrolment->status, self::INACTIVE_ENROLMENT_STATUSES, true)) {
                return null;
            }

            return ['enrolment', 'Enrolled: '.$title, "You have been enrolled in \"{$title}\".", $url, $data];
        }

        return match ($enrolment->status) {
            'enrolled', 'active' => ['enrolment', 'Enrolment active: '.$title, "Your enrolment in \"{$title}\" is active.", $url, $data],
            'completed' => ['enrolment', 'Course completed: '.$title, "Your enrolment in \"{$title}\" has been marked as completed.", $url, $data],
            'withdrawn', 'cancelled' => ['enrolment', 'Enrolment '.$enrolment->status.': '.$title, "Your enrolment in \"{$title}\" has been {$enrolment->status}.", null, $data],
            'failed' => ['enrolment', 'Course result: '.$title, "Your enrolment in \"{$title}\" has been marked as not passed.", $url, $data],
            default => null,
        };
    }

    /* ---------------------------------------------------------------- Lessons */

    private function lesson(Lesson $lesson, string $event): void
    {
        if (! $lesson->is_published || ($event === 'updated' && ! $lesson->wasChanged('is_published'))) {
            return;
        }

        $module = $lesson->module()->first();

        if (! $module || ! $module->is_published) {
            return;
        }

        $course = $module->course()->first();

        if (! $course) {
            return;
        }

        $this->notifications->sendToMany(
            self::learnerIds($course),
            'lesson_published',
            'New lesson: '.$lesson->title,
            "A new lesson \"{$lesson->title}\" is available in \"{$course->title}\".",
            self::route('learning.lesson.show', $lesson),
            ['course_id' => $course->id, 'lesson_id' => $lesson->id, 'module_id' => $module->id]
        );
    }

    /* ---------------------------------------------------------------- Assignments */

    private function assessment(Assessment $assessment, string $event): void
    {
        if (! $assessment->is_published || ! $assessment->course_id) {
            return;
        }

        $course = $assessment->course()->first();

        if (! $course) {
            return;
        }

        $label = ucfirst((string) ($assessment->type ?: 'assessment'));
        $due = $assessment->due_at ? ' Due '.$assessment->due_at->format('d M Y H:i').'.' : '';
        $data = [
            'course_id' => $course->id,
            'assessment_id' => $assessment->id,
            'due_at' => $assessment->due_at?->toIso8601String(),
        ];
        $url = self::route('learning.assessment.show', $assessment);

        if ($event === 'created' || $assessment->wasChanged('is_published')) {
            $this->notifications->sendToMany(
                self::learnerIds($course),
                'assignment_published',
                "New {$assessment->type}: {$assessment->title}",
                "{$label} \"{$assessment->title}\" has been published in \"{$course->title}\".{$due}",
                $url,
                $data
            );

            return;
        }

        if ($assessment->wasChanged('due_at') && $assessment->due_at) {
            $this->notifications->sendToMany(
                self::learnerIds($course),
                'assignment_due_date_changed',
                'Due date changed: '.$assessment->title,
                "The due date for \"{$assessment->title}\" in \"{$course->title}\" is now ".$assessment->due_at->format('d M Y H:i').'.',
                $url,
                $data
            );
        }
    }

    private function attempt(AssessmentAttempt $attempt, string $event): void
    {
        $assessment = $attempt->assessment()->first();

        if (! $assessment) {
            return;
        }

        $data = [
            'course_id' => $assessment->course_id,
            'assessment_id' => $assessment->id,
            'attempt_id' => $attempt->id,
        ];

        // A new submission that still needs a person to grade it.
        if ($event === 'created') {
            if ($attempt->status !== 'submitted' || ! $assessment->course_id) {
                return;
            }

            $course = $assessment->course()->first();
            $learner = $attempt->user()->first();

            $this->notifications->sendToMany(
                $course?->instructors()->pluck('users.id') ?? [],
                'assignment_submitted',
                'New submission: '.$assessment->title,
                ($learner?->name ?? 'A participant')." submitted \"{$assessment->title}\" in \"".($course?->title ?? 'a course').'".',
                $course ? self::route('instructor.courses.manage', $course) : null,
                $data
            );

            return;
        }

        $reviewed = $attempt->wasChanged(['status', 'score', 'percentage', 'instructor_feedback']);

        if (! $reviewed) {
            return;
        }

        $graded = $attempt->status === 'graded';

        if (! $graded && blank($attempt->instructor_feedback)) {
            return;
        }

        $result = $attempt->percentage !== null
            ? ' Score: '.rtrim(rtrim(number_format((float) $attempt->percentage, 2), '0'), '.').'%.'
            : ($attempt->score !== null ? ' Score: '.rtrim(rtrim(number_format((float) $attempt->score, 2), '0'), '.').'.' : '');
        $feedback = filled($attempt->instructor_feedback) ? ' Feedback: '.Str::limit((string) $attempt->instructor_feedback, 160) : '';

        $this->notifications->sendToMany(
            [$attempt->user_id],
            $graded ? 'assignment_graded' : 'assignment_feedback',
            ($graded ? 'Graded: ' : 'Feedback: ').$assessment->title,
            ($graded ? "Your submission for \"{$assessment->title}\" has been graded." : "You have new feedback on \"{$assessment->title}\".").$result.$feedback,
            self::route('learning.assessment.show', $assessment),
            $data + ['score' => $attempt->score, 'percentage' => $attempt->percentage]
        );
    }

    /* ---------------------------------------------------------------- Announcements */

    private function announcement(CourseAnnouncement $announcement, string $event): void
    {
        // Scheduled (future) announcements are not notified; there is no scheduler for them yet.
        if ($event !== 'created' || ! $announcement->course_id) {
            return;
        }

        if ($announcement->published_at && $announcement->published_at->isFuture()) {
            return;
        }

        $course = $announcement->course()->first();

        if (! $course) {
            return;
        }

        $this->notifications->sendToMany(
            self::learnerIds($course),
            'course_announcement',
            'Announcement: '.$announcement->title,
            Str::limit(strip_tags((string) $announcement->body), 240)." ({$course->title})",
            self::route('learning.course.dashboard', $course),
            ['course_id' => $course->id, 'announcement_id' => $announcement->id]
        );
    }

    /* ---------------------------------------------------------------- Course applications */

    private function courseApplication(CourseApplication $application, string $event): void
    {
        if ($event !== 'updated' || ! $application->wasChanged('status')) {
            return;
        }

        if (in_array($application->status, ['draft', 'submitted'], true)) {
            return;
        }

        $call = $application->courseCall()->first();
        $name = $call?->title ?? 'your course application';
        $status = str_replace('_', ' ', (string) $application->status);

        $message = match ($application->status) {
            'approved' => "Your application for \"{$name}\" has been approved.",
            'rejected' => "Your application for \"{$name}\" was not successful.",
            'returned_for_revision', 'revision_requested' => "Your application for \"{$name}\" needs changes. Please revise and resubmit it.",
            default => "Your application for \"{$name}\" is now {$status}.",
        };

        if (filled($application->reviewer_comments)) {
            $message .= ' Comments: '.Str::limit((string) $application->reviewer_comments, 160);
        }

        $this->notifications->sendToMany(
            [$application->user_id],
            'course_application',
            'Application '.$status.': '.$name,
            $message,
            self::route('participant.course-calls.index', ['tab' => 'submissions']),
            ['course_call_id' => $application->course_call_id, 'course_application_id' => $application->id, 'status' => $application->status]
        );
    }

    /* ---------------------------------------------------------------- Mentorship */

    private function mentorMatch(MentorMatch $match, string $event): void
    {
        if ($event === 'updated' && ! $match->wasChanged('status')) {
            return;
        }

        $mentor = $match->mentor()->first();
        $mentee = $match->mentee()->first();
        $data = ['mentor_match_id' => $match->id, 'status' => $match->status];
        $url = self::route('mentorship.dashboard');

        if ($event === 'created') {
            $this->notifications->sendToMany([$match->mentee_user_id], 'mentorship_match', 'New mentor match',
                'You have been matched with '.($mentor?->name ?? 'a mentor').'.', $url, $data);
            $this->notifications->sendToMany([$match->mentor_user_id], 'mentorship_match', 'New mentee match',
                'You have been matched with '.($mentee?->name ?? 'a mentee').'.', $url, $data);

            return;
        }

        $this->notifications->sendToMany([$match->mentor_user_id, $match->mentee_user_id], 'mentorship_match',
            'Mentorship match updated', 'Your mentorship match is now '.str_replace('_', ' ', (string) $match->status).'.', $url, $data);
    }

    private function mentorshipSession(MentorshipSession $session, string $event): void
    {
        if ($event === 'updated' && ! $session->wasChanged(['scheduled_at', 'status'])) {
            return;
        }

        $match = $session->match()->first();

        if (! $match) {
            return;
        }

        $when = $session->scheduled_at?->format('d M Y H:i');
        $name = $session->title ?: 'Mentorship session';

        [$title, $message] = match (true) {
            $event === 'created' => ['Session scheduled: '.$name, "A mentorship session \"{$name}\" has been scheduled".($when ? " for {$when}" : '').'.'],
            $session->wasChanged('scheduled_at') => ['Session rescheduled: '.$name, "The mentorship session \"{$name}\" has moved".($when ? " to {$when}" : '').'.'],
            default => ['Session '.str_replace('_', ' ', (string) $session->status).': '.$name, "The mentorship session \"{$name}\" is now ".str_replace('_', ' ', (string) $session->status).'.'],
        };

        $this->notifications->sendToMany(
            [$match->mentor_user_id, $match->mentee_user_id],
            'mentorship_session',
            $title,
            $message,
            self::route('mentorship.dashboard'),
            ['mentor_match_id' => $match->id, 'mentorship_session_id' => $session->id, 'scheduled_at' => $session->scheduled_at?->toIso8601String(), 'status' => $session->status]
        );
    }

    /* ---------------------------------------------------------------- Jobs */

    private function jobApplication(JobApplication $application, string $event): void
    {
        if ($event !== 'updated' || ! $application->wasChanged('status')) {
            return;
        }

        $job = $application->job()->first();
        $name = $job?->title ?? 'a job';
        $status = str_replace('_', ' ', (string) $application->status);

        $message = match ($application->status) {
            'interview' => "You have been invited to interview for \"{$name}\".",
            'offer' => "You have received an offer for \"{$name}\".",
            'hired' => "Congratulations! You have been hired for \"{$name}\".",
            'rejected' => "Your application for \"{$name}\" was not successful.",
            default => "Your application for \"{$name}\" is now {$status}.",
        };

        $this->notifications->sendToMany(
            [$application->user_id],
            'job_application',
            'Job application '.$status.': '.$name,
            $message,
            self::route('jobs.applications'),
            ['job_id' => $application->job_id, 'job_application_id' => $application->id, 'status' => $application->status]
        );
    }

    /* ---------------------------------------------------------------- Helpers */

    /** Ids of participants actively enrolled in a course. */
    public static function learnerIds(Course $course): array
    {
        return Enrolment::query()
            ->where('course_id', $course->id)
            ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', self::INACTIVE_ENROLMENT_STATUSES))
            ->pluck('user_id')
            ->all();
    }

    private static function route(string $name, mixed $parameters = []): ?string
    {
        if ($parameters === null) {
            return null;
        }

        try {
            return route($name, $parameters);
        } catch (\Throwable) {
            return null;
        }
    }
}
