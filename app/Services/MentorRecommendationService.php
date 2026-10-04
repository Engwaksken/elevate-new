<?php
namespace App\Services;

use App\Models\MenteeProfile;
use App\Models\MentorProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MentorRecommendationService
{
    public function __construct(private readonly CareerAiService $ai)
    {
    }

    /**
     * Keyword-overlap ranking. Deterministic and always available.
     */
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

    /**
     * Rank mentors with the configured platform AI, falling back to the
     * keyword ranking when AI is unavailable or returns an unusable answer.
     */
    public function recommendWithAi(MenteeProfile $mentee, int $limit = 10): Collection
    {
        $pool = $this->recommend($mentee, 30);

        if ($pool->isEmpty()) {
            return $pool;
        }

        try {
            $ranked = $this->aiRank($mentee, $pool);

            if ($ranked->isNotEmpty()) {
                return $ranked->take($limit)->values();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $pool->take($limit)->values();
    }

    private function aiRank(MenteeProfile $mentee, Collection $pool): Collection
    {
        $mentee->loadMissing('user');

        $candidates = $pool->values()->map(fn (MentorProfile $mentor) => [
            'profile_id' => $mentor->id,
            'name' => $mentor->user?->name,
            'industry' => $mentor->industry,
            'job_title' => $mentor->job_title,
            'mentoring_areas' => $mentor->mentoring_areas,
            'skills' => $mentor->skills,
            'bio' => Str::limit((string) $mentor->professional_bio, 300),
        ])->all();

        $system = 'You are a mentorship matching assistant. You rank mentors for a mentee based on fit. '
            .'Respond with ONLY a JSON array, no prose, no code fences. '
            .'Each item must be {"profile_id": <int>, "score": <0-100>, "reason": "<short reason>"}, ordered best first.';

        $prompt = 'Mentee profile: '.json_encode([
            'name' => $mentee->user?->name,
            'career_goals' => $mentee->career_goals,
            'skills' => $mentee->skills,
            'support_needs' => $mentee->support_needs,
            'preferred_mentor_areas' => $mentee->preferred_mentor_areas,
        ])."\n\nCandidate mentors: ".json_encode($candidates);

        $response = $this->ai->generate('mentor_matching', $system, $prompt, $mentee->user_id);
        $decoded = $this->decode($response['text'] ?? '');

        if (! is_array($decoded)) {
            return collect();
        }

        $byId = $pool->keyBy('id');
        $ranked = collect();

        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }

            $profileId = (int) ($item['profile_id'] ?? 0);
            $mentor = $byId->get($profileId);

            if (! $mentor) {
                continue;
            }

            $mentor->recommendation_score = max(0, min(100, (float) ($item['score'] ?? 0)));
            $mentor->recommendation_reason = (string) ($item['reason'] ?? '');
            $ranked->push($mentor);
        }

        return $ranked;
    }

    private function decode(string $text): mixed
    {
        $text = trim($text);

        if (preg_match('/```(?:json)?\s*(.*?)```/s', $text, $matches)) {
            $text = trim($matches[1]);
        }

        $start = strpos($text, '[');

        if ($start !== false) {
            $text = substr($text, $start);
        }

        return json_decode($text, true);
    }
}
