<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Enrolment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Spots returning participants (people who studied with us before) and accounts that
 * share a phone number with another participant, which usually means a second registration.
 * Works on a page of users at a time to avoid per-row queries.
 */
class ParticipantHistoryService
{
    /**
     * @param  iterable<int|User>  $users
     * @return Collection<int, array{returning: bool, courses: Collection, completed: int, certificates: int, summary: string}>
     *         keyed by user id; $excludeCourseId leaves out the course being reviewed or enrolled in.
     */
    public function summaries(iterable $users, ?int $excludeCourseId = null): Collection
    {
        $ids = $this->ids($users);

        if ($ids->isEmpty()) {
            return collect();
        }

        $enrolments = Enrolment::with('course:id,title')
            ->whereIn('user_id', $ids)
            ->when($excludeCourseId, fn ($query) => $query->where('course_id', '!=', $excludeCourseId))
            ->orderBy('created_at')
            ->get()
            ->groupBy('user_id');

        $certificates = Certificate::whereIn('user_id', $ids)
            ->when($excludeCourseId, fn ($query) => $query->where(fn ($q) => $q->whereNull('course_id')->orWhere('course_id', '!=', $excludeCourseId)))
            ->selectRaw('user_id, count(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        return $ids->mapWithKeys(function (int $id) use ($enrolments, $certificates) {
            $courses = ($enrolments->get($id) ?? collect())
                ->filter(fn (Enrolment $enrolment) => $enrolment->course)
                ->map(fn (Enrolment $enrolment) => [
                    'title' => $enrolment->course->title,
                    'status' => (string) $enrolment->status,
                    'year' => ($enrolment->enrolled_at ?? $enrolment->created_at)?->format('Y'),
                ])
                ->values();

            $completed = $courses->where('status', 'completed')->count();
            $certificateCount = (int) ($certificates[$id] ?? 0);
            $returning = $courses->isNotEmpty() || $certificateCount > 0;

            return [$id => [
                'returning' => $returning,
                'courses' => $courses,
                'completed' => $completed,
                'certificates' => $certificateCount,
                'summary' => $returning
                    ? $courses->count().' previous '.str('course')->plural($courses->count())
                        .' · '.$completed.' completed · '.$certificateCount.' '.str('certificate')->plural($certificateCount)
                    : 'First course with us',
            ]];
        });
    }

    /**
     * Other participant accounts with the same phone number (compared on the last 9 digits,
     * so 0772…, +256772… and 256 772… match).
     *
     * @param  iterable<User>  $users
     * @return Collection<int, Collection<int, User>> keyed by user id; only users with matches
     */
    public function possibleDuplicates(iterable $users): Collection
    {
        $users = collect($users)->filter(fn ($user) => $user instanceof User);
        $suffixes = $users
            ->mapWithKeys(fn (User $user) => [$user->id => $this->phoneKey($user->phone)])
            ->filter();

        if ($suffixes->isEmpty()) {
            return collect();
        }

        $normalised = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '')";

        $candidates = User::query()
            ->where('user_type', 'participant')
            ->whereNotNull('phone')
            ->where(function ($query) use ($suffixes, $normalised) {
                foreach ($suffixes->unique() as $suffix) {
                    $query->orWhereRaw("{$normalised} LIKE ?", ['%'.$suffix]);
                }
            })
            ->get(['id', 'name', 'email', 'phone', 'participant_code'])
            ->groupBy(fn (User $candidate) => $this->phoneKey($candidate->phone));

        return $suffixes
            ->map(fn (string $suffix, int $userId) => ($candidates->get($suffix) ?? collect())
                ->reject(fn (User $candidate) => $candidate->id === $userId)
                ->values())
            ->filter(fn (Collection $matches) => $matches->isNotEmpty());
    }

    private function phoneKey(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return strlen($digits) >= 9 ? substr($digits, -9) : null;
    }

    private function ids(iterable $users): Collection
    {
        return collect($users)
            ->map(fn ($user) => (int) ($user instanceof User ? $user->id : $user))
            ->filter()
            ->unique()
            ->values();
    }
}
