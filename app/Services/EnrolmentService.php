<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrolment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Enrolment rules:
 *
 *  - A participant takes at most ONE chosen course per cohort. Enrolments
 *    without a cohort count as their own group, so self-enrolment (which has
 *    no cohort) is also limited to one chosen course.
 *  - Compulsory courses (courses another course lists as compulsory) do not
 *    count towards that limit. They are enrolled automatically, in the same
 *    cohort, whenever a participant is enrolled in the course that needs them,
 *    and existing participants are caught up when a compulsory course is added.
 *  - Withdrawn or cancelled enrolments free the slot.
 */
class EnrolmentService
{
    /** Enrolments in these statuses no longer occupy the participant's course slot. */
    public const INACTIVE_STATUSES = ['withdrawn', 'cancelled'];

    /**
     * Enrol a user in a course and, in turn, that course's compulsory courses.
     * Recursion terminates because a course is only cascaded the first time its
     * enrolment is created.
     *
     * @throws ValidationException when the one-course-per-cohort rule is broken
     */
    public function enrol(User $user, Course $course, array $attributes = []): Enrolment
    {
        $existing = Enrolment::where('course_id', $course->id)->where('user_id', $user->id)->first();

        if (! $existing) {
            $this->assertCanEnrol($user, $course, $attributes['cohort_id'] ?? null);
        }

        return DB::transaction(function () use ($user, $course, $attributes) {
            $enrolment = Enrolment::firstOrCreate(
                ['course_id' => $course->id, 'user_id' => $user->id],
                array_merge(['status' => 'enrolled', 'enrolled_at' => now()], $attributes)
            );

            if ($enrolment->wasRecentlyCreated) {
                $this->enrolCompulsory($user, $course, $enrolment->cohort_id);
            }

            return $enrolment;
        });
    }

    /**
     * The participant's other active, non-compulsory enrolment in the cohort,
     * if any. Pass $ignoreEnrolmentId when moving an existing enrolment.
     */
    public function conflictingEnrolment(User|int $user, Course|int $course, ?int $cohortId, ?int $ignoreEnrolmentId = null): ?Enrolment
    {
        $course = $course instanceof Course ? $course : Course::find($course);

        if (! $course || $this->isCompulsory($course)) {
            return null;
        }

        return Enrolment::with('course:id,title')
            ->where('user_id', $user instanceof User ? $user->id : $user)
            ->where('course_id', '!=', $course->id)
            ->when($cohortId, fn ($q) => $q->where('cohort_id', $cohortId), fn ($q) => $q->whereNull('cohort_id'))
            ->when($ignoreEnrolmentId, fn ($q) => $q->whereKeyNot($ignoreEnrolmentId))
            ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', self::INACTIVE_STATUSES))
            ->whereHas('course', fn ($q) => $q->whereDoesntHave('compulsoryFor'))
            ->first();
    }

    /**
     * @throws ValidationException
     */
    public function assertCanEnrol(User|int $user, Course|int $course, ?int $cohortId, ?int $ignoreEnrolmentId = null, string $field = 'course_id'): void
    {
        $conflict = $this->conflictingEnrolment($user, $course, $cohortId, $ignoreEnrolmentId);

        if ($conflict) {
            $title = $conflict->course?->title ?? 'another course';

            throw ValidationException::withMessages([
                $field => $cohortId
                    ? "This participant is already enrolled in \"{$title}\" in this cohort. A participant can only take one course per cohort (compulsory courses excepted)."
                    : "This participant is already enrolled in \"{$title}\". A participant can only take one course at a time (compulsory courses excepted).",
            ]);
        }
    }

    /** A compulsory course is one that another course lists as compulsory. */
    public function isCompulsory(Course $course): bool
    {
        return $course->compulsoryFor()->exists();
    }

    /**
     * Catch up participants already enrolled in $course on its compulsory
     * courses (e.g. after a compulsory course was added to it).
     * Returns the number of enrolments created.
     */
    public function syncCompulsoryFor(Course $course): int
    {
        $compulsory = $course->compulsoryCourses()->get();

        if ($compulsory->isEmpty()) {
            return 0;
        }

        $created = 0;

        Enrolment::with('user')
            ->where('course_id', $course->id)
            ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', self::INACTIVE_STATUSES))
            ->whereHas('user')
            ->chunkById(200, function ($enrolments) use ($compulsory, &$created) {
                foreach ($enrolments as $enrolment) {
                    foreach ($compulsory as $required) {
                        $created += $this->enrolCompulsoryCourse($enrolment->user, $required, $enrolment->cohort_id);
                    }
                }
            });

        return $created;
    }

    /** Run syncCompulsoryFor() for every course that has compulsory courses. */
    public function syncAllCompulsory(): int
    {
        return Course::whereHas('compulsoryCourses')->get()
            ->sum(fn (Course $course) => $this->syncCompulsoryFor($course));
    }

    /** Enrol the user in $course's compulsory courses (used by imports that create enrolments directly). */
    public function enrolCompulsory(User $user, Course $course, ?int $cohortId): void
    {
        foreach ($course->compulsoryCourses()->get() as $compulsory) {
            $this->enrolCompulsoryCourse($user, $compulsory, $cohortId);
        }
    }

    /** Enrols in a compulsory course (and its own compulsory courses); 1 if created. */
    private function enrolCompulsoryCourse(User $user, Course $compulsory, ?int $cohortId): int
    {
        $enrolment = $this->enrol($user, $compulsory, array_filter(['cohort_id' => $cohortId], fn ($v) => $v !== null));

        return $enrolment->wasRecentlyCreated ? 1 : 0;
    }
}
