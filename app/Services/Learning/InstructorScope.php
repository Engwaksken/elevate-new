<?php

namespace App\Services\Learning;

use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Which participants of a course a staff member works with.
 *
 * - Admins, the course's lead instructor(s), and instructors with no branch
 *   assigned see the whole course (today's behaviour).
 * - Any other instructor is a "branch instructor": they see participants in
 *   the course's cohorts that run at their branches, plus cohort-less
 *   participants whose profile branch is one of theirs.
 *
 * Shared course content (modules, shared lessons, assessments, questions,
 * course settings) can be changed by everyone with full access. Once a course
 * has a lead instructor, branch instructors can only add material, deadlines
 * and announcements for their own cohorts.
 */
class InstructorScope
{
    /** @var array<string, array> */
    private array $cache = [];

    public function isAdmin(User $user): bool
    {
        // Same rule as CourseManagementController::authorise(). A courses.edit
        // permission alone does not lift the branch limit, so a role edit in the
        // admin screens cannot silently undo it.
        return $user->isSuperAdmin()
            || $user->hasAnyRole(['administrator', 'super-administrator', 'super-admin']);
    }

    /** True when the user sees every participant of the course. */
    public function seesWholeCourse(User $user, Course $course): bool
    {
        return $this->resolve($user, $course)['all'];
    }

    /** True when the user may change the course's shared content. */
    public function canEditSharedContent(User $user, Course $course): bool
    {
        return $this->resolve($user, $course)['edit_shared'];
    }

    /**
     * Cohorts of this course the user works with (all of the course's cohorts
     * for someone with full access).
     *
     * @return Collection<int, Cohort>
     */
    public function cohorts(User $user, Course $course): Collection
    {
        $ids = $this->resolve($user, $course)['cohort_ids'];

        return $course->cohorts()
            ->when($ids !== null, fn ($q) => $q->whereIn('cohorts.id', $ids ?: [0]))
            ->orderBy('cohorts.name')
            ->get();
    }

    /** True when the user may target this cohort (deadline, announcement, extra lesson). */
    public function coversCohort(User $user, Course $course, ?int $cohortId): bool
    {
        $scope = $this->resolve($user, $course);

        if ($cohortId === null) {
            // "Whole course" is shared content.
            return $scope['edit_shared'];
        }

        if (! $course->cohorts()->whereKey($cohortId)->exists()) {
            return false;
        }

        return $scope['cohort_ids'] === null || in_array($cohortId, $scope['cohort_ids'], true);
    }

    /** Limit an enrolments query (for this course) to the participants the user works with. */
    public function scopeEnrolments($query, User $user, Course $course)
    {
        $scope = $this->resolve($user, $course);

        if ($scope['all']) {
            return $query;
        }

        return $query->where(function ($q) use ($scope) {
            $q->whereIn('enrolments.cohort_id', $scope['cohort_ids'] ?: [0])
                ->orWhere(fn ($q) => $q->whereNull('enrolments.cohort_id')
                    ->whereHas('user.profile', fn ($p) => $p->whereIn('branch_id', $scope['branch_ids'] ?: [0])));
        });
    }

    /** Ids of the course's participants the user works with, or null for "everyone". */
    public function participantIds(User $user, Course $course): ?array
    {
        if ($this->seesWholeCourse($user, $course)) {
            return null;
        }

        return $this->scopeEnrolments(Enrolment::query()->where('enrolments.course_id', $course->id), $user, $course)
            ->pluck('enrolments.user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** Limit a query with a user_id column to the participants the user works with. */
    public function scopeParticipants($query, User $user, Course $course, string $column = 'user_id')
    {
        $ids = $this->participantIds($user, $course);

        return $ids === null ? $query : $query->whereIn($column, $ids ?: [0]);
    }

    public function coversParticipant(User $user, Course $course, int $participantId): bool
    {
        $ids = $this->participantIds($user, $course);

        return $ids === null || in_array($participantId, $ids, true);
    }

    /**
     * The course's instructors who work with this participant (for notifications
     * about their submissions and extension requests).
     *
     * @return Collection<int, User>
     */
    public function instructorsFor(Course $course, int $participantId): Collection
    {
        return $course->instructors()->get()
            ->filter(fn (User $instructor) => $this->coversParticipant($instructor, $course, $participantId))
            ->values();
    }

    private function resolve(User $user, Course $course): array
    {
        $key = $user->id.':'.$course->id;

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $hasLead = $course->instructors()->wherePivot('is_lead', true)->exists();

        if ($this->isAdmin($user)) {
            return $this->cache[$key] = ['all' => true, 'edit_shared' => true, 'cohort_ids' => null, 'branch_ids' => []];
        }

        $isLead = $course->instructors()->whereKey($user->id)->wherePivot('is_lead', true)->exists();
        $branchIds = $user->branches()->pluck('branches.id')->map(fn ($id) => (int) $id)->all();

        if ($isLead || $branchIds === []) {
            return $this->cache[$key] = ['all' => true, 'edit_shared' => $isLead || ! $hasLead, 'cohort_ids' => null, 'branch_ids' => $branchIds];
        }

        $cohortIds = $course->cohorts()
            ->where(fn ($q) => $q->whereIn('cohorts.branch_id', $branchIds)
                ->orWhereHas('branches', fn ($b) => $b->whereIn('branches.id', $branchIds)))
            ->pluck('cohorts.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $this->cache[$key] = [
            'all' => false,
            'edit_shared' => ! $hasLead,
            'cohort_ids' => $cohortIds,
            'branch_ids' => $branchIds,
        ];
    }
}
