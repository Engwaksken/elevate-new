<?php
namespace App\Services;

use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Collection;

class JobRecommendationService
{
    public function recommend(User $user, int $limit = 20): Collection
    {
        $resume = $user->resumes()->with('skills')->where('is_default',true)->first()
            ?? $user->resumes()->with('skills')->latest()->first();

        $profileKeywords = collect(explode(',', (string)optional($user->profile)->career_interests))
            ->map(fn($v)=>mb_strtolower(trim($v)))
            ->filter();

        $skillKeywords = collect($resume?->skills ?? [])
            ->pluck('skill')
            ->map(fn($v)=>mb_strtolower(trim($v)));

        $keywords = $profileKeywords->merge($skillKeywords)->unique();

        return Job::with('employer')
            ->where('status','published')
            ->get()
            ->map(function($job) use ($keywords) {
                $jobSkills = collect($job->skills ?? [])->map(fn($v)=>mb_strtolower(trim($v)));
                $text = mb_strtolower($job->title.' '.$job->description.' '.$job->requirements);

                $matchedSkills = $keywords->intersect($jobSkills)->count();
                $textMatches = $keywords->filter(fn($k)=>$k !== '' && str_contains($text,$k))->count();

                $score = min(100, ($matchedSkills * 20) + ($textMatches * 10));
                $job->recommendation_score = $score;
                return $job;
            })
            ->sortByDesc('recommendation_score')
            ->take($limit)
            ->values();
    }
}
