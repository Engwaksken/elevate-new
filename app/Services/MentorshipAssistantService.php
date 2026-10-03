<?php

namespace App\Services;

use App\Models\MentorMatch;
use App\Models\MentorshipSession;
use App\Models\User;

class MentorshipAssistantService
{
    public function __construct(private readonly CareerAiService $ai)
    {
    }

    /**
     * Answer a participant's career question, grounded in her own mentorship data.
     */
    public function reply(User $user, string $message, array $history = []): array
    {
        $context = $this->context($user);
        $history = collect($history)
            ->filter(fn ($entry) => is_array($entry) && isset($entry['role'], $entry['content']))
            ->take(-8)
            ->map(fn ($entry) => strtoupper((string) $entry['role']).': '.trim((string) $entry['content']))
            ->implode("\n");

        $system = "You are ElevateHer360's AI Career Mentor, a warm, practical guide for young women on a learning and mentorship platform. "
            .'Help the participant answer career questions and navigate her career path: job search, interviews, CVs, skills, further study, entrepreneurship, workplace challenges and how to use her mentorship relationship. '
            .'Use the participant context below when it is relevant. Do not invent personal facts that are not in the context. '
            .'Be encouraging and specific, suggest concrete next steps, keep answers short (under 220 words) and reply in plain text without Markdown.'
            ."\n\nParticipant context:\n".$context;

        $prompt = trim(($history !== '' ? "Recent conversation:\n{$history}\n\n" : '')."Question: {$message}");

        try {
            $result = $this->ai->generate('mentorship_assistant', $system, $prompt, $user->id);
            $text = trim((string) ($result['text'] ?? ''));

            if ($text !== '') {
                return ['message' => $text, 'source' => 'ai'];
            }
        } catch (\Throwable $e) {
            // Fall through to the offline guidance below.
        }

        return ['message' => $this->fallback($user, $message), 'source' => 'guidance'];
    }

    public function context(User $user): string
    {
        $user->loadMissing('profile');
        $profile = $user->profile;

        $goals = $user->goals()->latest()->take(10)->get()
            ->map(fn ($goal) => sprintf('- %s (%s, %s%%%s)', $goal->title, $goal->status, number_format((float) $goal->progress_percent, 0), $goal->target_date ? ', due '.$goal->target_date->format('d M Y') : ''))
            ->implode("\n");

        $matchIds = MentorMatch::where('mentee_user_id', $user->id)->pluck('id');
        $sessions = MentorshipSession::with('match.mentor')
            ->whereIn('mentor_match_id', $matchIds)
            ->orderByDesc('scheduled_at')
            ->take(5)
            ->get()
            ->map(function ($session) {
                $mentor = $session->match?->mentor?->name;

                return sprintf('- %s on %s%s (%s)', $session->title ?: 'Session', $session->scheduled_at?->format('d M Y') ?? 'date TBC', $mentor ? ' with '.$mentor : '', $session->status);
            })
            ->implode("\n");

        return implode("\n", array_filter([
            'Name: '.$user->name,
            $profile?->education_level ? 'Education: '.$profile->education_level : null,
            $profile?->employment_status ? 'Employment: '.$profile->employment_status : null,
            $profile?->career_interests ? 'Career interests: '.$profile->career_interests : null,
            $profile?->district || $profile?->country ? 'Location: '.trim(($profile?->district ?? '').' '.($profile?->country ?? '')) : null,
            $goals !== '' ? "Goals:\n".$goals : 'Goals: none recorded yet',
            $sessions !== '' ? "Recent mentorship sessions:\n".$sessions : 'Mentorship sessions: none scheduled yet',
        ]));
    }

    private function fallback(User $user, string $message): string
    {
        $first = trim(explode(' ', trim($user->name))[0]);
        $goals = $user->goals()->where('status', '!=', 'completed')->count();

        $base = "Hi {$first}, I'm here to help with your career. ";

        if ($goals > 0) {
            $base .= "You're currently tracking {$goals} open goal".($goals === 1 ? '' : 's').". ";
        }

        return $base.'The AI career mentor is not available right now. In the meantime: write down one concrete career question, '
            .'bring it to your next mentorship session, and set a small next step you can finish this week. Configure the system AI provider in Admin to enable full AI guidance.';
    }
}
