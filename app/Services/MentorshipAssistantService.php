<?php

namespace App\Services;

use App\Models\CareerActionItem;
use App\Models\CareerMentorMessage;
use App\Models\MentorMatch;
use App\Models\MentorshipGoal;
use App\Models\MentorshipSession;
use App\Models\ParticipantGoal;
use App\Models\User;
use App\Support\AiPlainText;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MentorshipAssistantService
{
    public function __construct(private readonly CareerAiService $ai)
    {
    }

    /**
     * Answer a participant's career question with the active AI provider,
     * grounded in the goals she has set and her mentorship activity.
     */
    public function reply(User $user, string $message, array $history = []): array
    {
        $message = trim($message);
        $goals = $this->openGoals($user);

        $system = "You are a career mentor on ElevateHer360, a learning and mentorship platform for young women. "
            .'Answer the participant\'s career questions (career paths, job search, interviews, CVs, skills, further study, entrepreneurship, workplace challenges, working with her mentor). '
            .'Base your guidance on the goals she has set: connect your answer to the most relevant goal by name, respect its target date and progress, and build on any mentor feedback. '
            .'If she has no goals yet, answer the question and suggest one specific goal she could set. If the question is unrelated to careers, briefly steer back to her goals. '
            .'Only use personal facts from the participant context; never invent achievements, employers or qualifications. '
            ."\n\nHow to write: sound like an experienced, kind human mentor talking to her, not like a report. "
            .'Use her first name once, write in short natural paragraphs with everyday words, and speak directly to her as "you". '
            .'Give one clear piece of advice with a short reason, then finish with two or three small, concrete things she can do this week as a short numbered list. '
            .'Keep it under 200 words. Write plain text only: no Markdown, no asterisks, no bold, no headings, no bullet symbols, and no long dashes; use commas or full stops instead. '
            .'Avoid filler such as "Great question", "I hope this helps" or "Good luck" and avoid abbreviations like "e.g.".'
            ."\n\nAfter your answer, add one final line that starts with ".self::ACTIONS_MARKER.' followed by a JSON array of the same two or three steps, '
            .'each as {"title": "short action, under 12 words", "due": "YYYY-MM-DD", "goal": "exact title of the related goal or empty"}. '
            .'Choose realistic due dates from today onwards, no later than the related goal\'s target date. This line is read by the app and is not shown to her.'
            ."\n\nParticipant context:\n".$this->context($user, $goals);

        // The stored conversation is the source of truth, so history survives page reloads and devices.
        $stored = CareerMentorMessage::where('user_id', $user->id)->latest('id')->take(8)->get()->reverse();
        $turns = ($stored->isNotEmpty() ? $stored->map(fn ($m) => ['role' => $m->role, 'content' => $m->content]) : collect($history))
            ->filter(fn ($entry) => is_array($entry) && isset($entry['role'], $entry['content']))
            ->map(fn ($entry) => [
                'role' => strtolower((string) $entry['role']) === 'assistant' ? 'assistant' : 'user',
                'content' => Str::limit(trim((string) $entry['content']), 1500, ''),
            ])
            ->take(-8)
            ->values()
            ->all();

        $text = '';
        $steps = [];
        try {
            $result = $this->ai->generate('mentorship_assistant', $system, $message, $user->id, $turns);
            [$answer, $steps] = $this->splitActions((string) ($result['text'] ?? ''));
            $text = AiPlainText::clean($answer);
        } catch (\Throwable $e) {
            // Fall through to the goal-based guidance below.
        }

        $source = $text !== '' ? 'ai' : 'guidance';
        if ($text === '') {
            $text = $this->fallback($user, $goals);
            $steps = [];
        }

        CareerMentorMessage::create(['user_id' => $user->id, 'role' => 'user', 'content' => Str::limit($message, 1500, '')]);
        $reply = CareerMentorMessage::create(['user_id' => $user->id, 'role' => 'assistant', 'content' => $text, 'source' => $source]);
        $this->storeActions($user, $reply, $steps, $goals);

        return $reply->load('actions.goal')->toChatArray() + ['message' => $text];
    }

    public const ACTIONS_MARKER = 'ACTIONS:';

    /**
     * Separate the hidden "ACTIONS: [...]" line from the visible answer.
     *
     * @return array{0:string,1:array<int,array>}
     */
    public function splitActions(string $raw): array
    {
        $pos = strripos($raw, self::ACTIONS_MARKER);
        if ($pos === false) {
            return [$raw, []];
        }

        $json = trim(substr($raw, $pos + strlen(self::ACTIONS_MARKER)));
        $json = trim(preg_replace('/^```(?:json)?|```$/m', '', $json));
        $decoded = json_decode($json, true);

        return [rtrim(substr($raw, 0, $pos)), is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : []];
    }

    private function storeActions(User $user, CareerMentorMessage $reply, array $steps, Collection $goals): void
    {
        $today = now()->startOfDay();
        foreach (array_slice($steps, 0, 3) as $step) {
            $title = Str::limit(AiPlainText::clean((string) ($step['title'] ?? '')), 185, '');
            if ($title === '') {
                continue;
            }

            $goalTitle = trim((string) ($step['goal'] ?? ''));
            $goal = $goalTitle !== '' ? $goals->first(fn ($g) => strcasecmp($g->title, $goalTitle) === 0) : null;

            // Keep model-suggested dates within today .. one year (or the goal's target date).
            try {
                $due = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', (string) ($step['due'] ?? ''))->startOfDay();
            } catch (\Throwable) {
                $due = $today->copy()->addWeek();
            }
            $latest = $goal?->target_date && $goal->target_date->gte($today) ? $goal->target_date : $today->copy()->addYear();
            $due = $due->lt($today) ? $today->copy() : ($due->gt($latest) ? $latest->copy() : $due);

            $reply->actions()->create([
                'user_id' => $user->id,
                'participant_goal_id' => $goal?->id,
                'title' => $title,
                'due_on' => $due->toDateString(),
                'status' => CareerActionItem::SUGGESTED,
            ]);
        }
    }

    public function context(User $user, ?Collection $goals = null): string
    {
        $user->loadMissing('profile');
        $profile = $user->profile;
        $goals ??= $this->openGoals($user);

        $goalLines = $goals->map(fn (ParticipantGoal $goal) => $this->describeGoal($goal))->implode("\n");

        $completed = ParticipantGoal::where('user_id', $user->id)->where('status', 'completed')
            ->latest('completed_at')->take(5)->pluck('title')->implode('; ');

        $matchIds = MentorMatch::where('mentee_user_id', $user->id)->pluck('id');

        $matchGoals = MentorshipGoal::whereIn('mentor_match_id', $matchIds)
            ->whereNotIn('status', ['completed', 'achieved', 'cancelled'])
            ->latest('id')->take(5)->get()
            ->map(fn ($goal) => sprintf('- %s (%s%%%s)%s', $goal->title, number_format((float) $goal->progress_percent, 0),
                $goal->target_date ? ', due '.$goal->target_date->format('d M Y') : '',
                $goal->description ? ': '.Str::limit($goal->description, 200) : ''))
            ->implode("\n");

        $sessions = MentorshipSession::with('match.mentor')
            ->whereIn('mentor_match_id', $matchIds)
            ->orderByDesc('scheduled_at')
            ->take(5)
            ->get()
            ->map(function ($session) {
                $mentor = $session->match?->mentor?->name;
                $actions = $session->agreed_actions ? ' - agreed actions: '.Str::limit((string) $session->agreed_actions, 200) : '';

                return sprintf('- %s on %s%s (%s)%s', $session->title ?: 'Session', $session->scheduled_at?->format('d M Y') ?? 'date TBC', $mentor ? ' with '.$mentor : '', $session->status, $actions);
            })
            ->implode("\n");

        return implode("\n", array_filter([
            'Name: '.$user->name,
            'Today: '.now()->format('d M Y'),
            $profile?->education_level ? 'Education: '.$profile->education_level : null,
            $profile?->employment_status ? 'Employment: '.$profile->employment_status : null,
            $profile?->career_interests ? 'Career interests: '.$profile->career_interests : null,
            $profile?->district || $profile?->country ? 'Location: '.trim(($profile?->district ?? '').' '.($profile?->country ?? '')) : null,
            $goalLines !== '' ? "Her active goals:\n".$goalLines : 'Her active goals: none set yet',
            $completed !== '' ? 'Goals already completed: '.$completed : null,
            $matchGoals !== '' ? "Goals agreed with her mentor:\n".$matchGoals : null,
            $sessions !== '' ? "Recent mentorship sessions:\n".$sessions : 'Mentorship sessions: none scheduled yet',
        ]));
    }

    private function openGoals(User $user): Collection
    {
        return ParticipantGoal::where('user_id', $user->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderByRaw("case priority when 'high' then 0 when 'medium' then 1 else 2 end")
            ->orderByRaw('target_date is null, target_date')
            ->take(10)
            ->get();
    }

    private function describeGoal(ParticipantGoal $goal): string
    {
        $number = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');

        $details = array_filter([
            $goal->category ? ucfirst((string) $goal->category) : null,
            $goal->priority ? ucfirst((string) $goal->priority).' priority' : null,
            str_replace('_', ' ', (string) $goal->status),
            number_format((float) $goal->progress_percent, 0).'% complete',
            $goal->target_value !== null ? 'at '.$number($goal->current_value).' of '.$number($goal->target_value).($goal->unit ? ' '.$goal->unit : '') : null,
            $goal->target_date ? 'due '.$goal->target_date->format('d M Y').($goal->target_date->isPast() ? ' (overdue)' : '') : null,
        ]);

        return '- '.$goal->title.' ['.implode(', ', $details).']'
            .($goal->description ? "\n  Description: ".Str::limit((string) $goal->description, 300) : '')
            .($goal->mentor_comment ? "\n  Mentor feedback: ".Str::limit((string) $goal->mentor_comment, 300) : '');
    }

    private function fallback(User $user, Collection $goals): string
    {
        $first = trim(explode(' ', trim($user->name))[0]);
        $text = "Hi {$first}, the AI mentor cannot give a detailed answer right now, but here is how to keep moving. ";

        $goal = $goals->first();
        if (! $goal) {
            return $text.'Start by adding a career goal in the Goals tab, for example a skill to learn or a number of job applications to send, with a target date. '
                .'Then bring your question to your next mentorship session and agree one small step you can finish this week.';
        }

        $text .= "Your top goal is \"{$goal->title}\", currently ".number_format((float) $goal->progress_percent, 0).'% complete';
        $text .= $goal->target_date ? ' and due '.$goal->target_date->format('d M Y').'. ' : '. ';

        if ($goal->mentor_comment) {
            $text .= 'Revisit your mentor\'s feedback: "'.Str::limit((string) $goal->mentor_comment, 160).'". ';
        }

        return $text.'Pick one action this week that moves this goal forward, update your progress in the Goals tab, and raise your question with your mentor at your next session.';
    }
}
