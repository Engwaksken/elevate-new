<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Decides who a user may start a chat with or add to a group.
 *
 *  - Staff can message every active staff member.
 *  - Staff who instruct courses (course_instructors) can also message the
 *    participants enrolled in those courses.
 *  - Participants can message other active participants and the
 *    instructors of the courses they are enrolled in.
 *
 * Mentors and employers have no chat.
 */
class ChatDirectory
{
    /** Enrolment states that still count as "in the course". */
    private const ACTIVE_ENROLMENT = ['enrolled', 'in_progress', 'completed'];

    public static function canChat(?User $user): bool
    {
        return $user !== null && $user->isActive() && (self::isStaffSide($user) || $user->isParticipant());
    }

    public static function isStaffSide(User $user): bool
    {
        return $user->isStaff() || $user->isSuperAdmin();
    }

    /** Everyone the viewer may contact (excluding themselves). */
    public function query(User $viewer): Builder
    {
        $query = User::query()->where('status', 'active')->whereKeyNot($viewer->id);

        if (self::isStaffSide($viewer)) {
            $courseIds = $this->instructedCourseIds($viewer);

            return $query->where(function (Builder $q) use ($courseIds) {
                $q->where('user_type', 'staff');
                if ($courseIds->isNotEmpty()) {
                    $q->orWhere(fn (Builder $p) => $this->participantsIn($p, $courseIds));
                }
            });
        }

        if ($viewer->isParticipant()) {
            $courseIds = $viewer->enrolments()->whereIn('status', self::ACTIVE_ENROLMENT)->pluck('course_id');

            return $query->where(function (Builder $q) use ($courseIds) {
                $q->where('user_type', 'participant');
                if ($courseIds->isNotEmpty()) {
                    $q->orWhere(fn (Builder $s) => $s->where('user_type', 'staff')
                        ->whereHas('instructedCourses', fn (Builder $c) => $c->whereIn('courses.id', $courseIds)));
                }
            });
        }

        return $query->whereRaw('1 = 0');
    }

    /** True when every id is someone the viewer may contact. */
    public function allows(User $viewer, Collection $ids): bool
    {
        $ids = $ids->map(fn ($id) => (int) $id)->filter()->unique();

        return $ids->isNotEmpty() && $this->query($viewer)->whereIn('id', $ids)->count() === $ids->count();
    }

    /**
     * Search the viewer's contacts for the people picker.
     *
     * @return Collection<int, array{id:int,name:string,meta:string}>
     */
    public function search(User $viewer, ?string $term = null, ?string $group = null, array $exclude = [], int $limit = 30): Collection
    {
        $query = $this->query($viewer)->whereNotIn('id', array_map('intval', $exclude));

        if ($group !== null && $group !== '') {
            $query = $this->applyGroup($viewer, $query, $group);
            $limit = 500;
        }

        if ($term = trim((string) $term)) {
            $query->where(fn (Builder $q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
        }

        return $query->orderBy('user_type', 'desc')->orderBy('name')->limit($limit)->get(['id', 'name', 'user_type'])
            ->map(fn (User $user) => self::row($user));
    }

    /**
     * Quick-add groups shown above the picker (e.g. "Everyone in Course X").
     *
     * @return array<string, string> key => label
     */
    public function groups(User $viewer): array
    {
        if (! self::isStaffSide($viewer)) {
            return [];
        }

        $groups = ['staff' => 'All staff'];
        $courseIds = $this->instructedCourseIds($viewer);
        if ($courseIds->isNotEmpty()) {
            Course::query()->whereIn('id', $courseIds)->orderBy('title')->get(['id', 'title'])
                ->each(function (Course $course) use (&$groups) {
                    $groups['course-'.$course->id] = 'Participants in '.$course->title;
                });
        }

        return $groups;
    }

    public static function row(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'meta' => match ($user->user_type) {
                'staff' => 'Staff',
                'participant' => 'Participant',
                default => ucfirst((string) $user->user_type),
            },
        ];
    }

    private function applyGroup(User $viewer, Builder $query, string $group): Builder
    {
        if ($group === 'staff') {
            return $query->where('user_type', 'staff');
        }

        if (preg_match('/^course-(\d+)$/', $group, $m) && $this->instructedCourseIds($viewer)->contains((int) $m[1])) {
            return $query->where(fn (Builder $p) => $this->participantsIn($p, collect([(int) $m[1]])));
        }

        return $query->whereRaw('1 = 0');
    }

    private function participantsIn(Builder $query, Collection $courseIds): Builder
    {
        return $query->where('user_type', 'participant')
            ->whereHas('enrolments', fn (Builder $e) => $e->whereIn('course_id', $courseIds)->whereIn('status', self::ACTIVE_ENROLMENT));
    }

    private function instructedCourseIds(User $viewer): Collection
    {
        return $viewer->instructedCourses()->pluck('courses.id')->map(fn ($id) => (int) $id);
    }
}
