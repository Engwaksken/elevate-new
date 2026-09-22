<?php
namespace App\Services;

use App\Models\MenteeProfile;
use App\Models\MentorProfile;
use Illuminate\Support\Collection;

class MentorRecommendationService
{
    public function recommend(MenteeProfile $mentee, int $limit = 10): Collection
    {
        $needs = collect($mentee->preferred_mentor_areas ?? [])
            ->merge($mentee->support_needs ?? [])
            ->map(fn($v)=>mb_strtolower(trim((string)$v)))
            ->filter()
            ->unique();

        return MentorProfile::with('user')
            ->where('status','approved')
            ->get()
            ->map(function ($mentor) use ($needs) {
                $areas = collect($mentor->mentoring_areas ?? [])
                    ->merge($mentor->skills ?? [])
                    ->map(fn($v)=>mb_strtolower(trim((string)$v)));

                $matches = $needs->intersect($areas)->count();
                $base = max(1, $needs->count());
                $score = round(($matches / $base) * 100, 2);

                $mentor->recommendation_score = $score;
                return $mentor;
            })
            ->sortByDesc('recommendation_score')
            ->take($limit)
            ->values();
    }
}
