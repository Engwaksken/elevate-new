<?php

namespace App\Services;

use App\Models\MenteeProfile;
use App\Models\MentorMatch;
use App\Models\User;

/**
 * Ensures a job-seeking participant has a mentor to prepare them for the
 * opportunity, ranking candidates with the platform AI where available.
 */
class MentorAssignmentService
{
    public function __construct(
        private readonly MentorRecommendationService $recommendations,
        private readonly NotificationDispatcher $dispatcher,
    ) {
    }

    public function ensureMentorForJobSeeker(User $user): void
    {
        try {
            if ($this->hasActiveMentor($user)) {
                return;
            }

            $mentee = MenteeProfile::firstOrCreate(['user_id' => $user->id]);
            $mentor = $this->recommendations->recommendWithAi($mentee, 1)->first();

            if ($mentor && ! $this->alreadyMatched($user, (int) $mentor->user_id)) {
                $match = MentorMatch::create([
                    'mentor_user_id' => $mentor->user_id,
                    'mentee_user_id' => $user->id,
                    'programme_id' => $mentee->programme_id,
                    'cohort_id' => $mentee->cohort_id,
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addMonths(3)->toDateString(),
                    'status' => 'active',
                    'matching_score' => $mentor->recommendation_score ?? null,
                ]);

                $this->dispatcher->notify(
                    $mentor->user,
                    'mentorship_match',
                    'New job-seeker mentee',
                    $user->name.' applied for a job and has been matched with you for career preparation.',
                    route('mentorship.dashboard'),
                    ['mentor_match_id' => $match->id, 'mentee_id' => $user->id],
                    false
                );

                $this->dispatcher->notify(
                    $user,
                    'mentorship_match',
                    'A mentor was assigned to you',
                    'We matched you with '.$mentor->user?->name.' to help prepare you for job opportunities.',
                    route('mentorship.dashboard'),
                    ['mentor_match_id' => $match->id],
                    false
                );

                return;
            }

            $officers = User::query()
                ->where('status', 'active')
                ->whereHas('roles', fn ($q) => $q->where('slug', 'placement-officer'))
                ->get();

            $this->dispatcher->notifyMany(
                $officers,
                'mentorship_assignment_needed',
                'Job-seeker needs a mentor',
                $user->name.' applied for a job and has no mentor yet. Please assign one.',
                route('admin.mentorship.matches.index'),
                ['mentee_id' => $user->id]
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function hasActiveMentor(User $user): bool
    {
        return MentorMatch::query()
            ->where('mentee_user_id', $user->id)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString()))
            ->exists();
    }

    private function alreadyMatched(User $user, int $mentorId): bool
    {
        return MentorMatch::query()
            ->where('mentee_user_id', $user->id)
            ->where('mentor_user_id', $mentorId)
            ->where('status', 'active')
            ->exists();
    }
}
