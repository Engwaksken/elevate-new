<?php

namespace App\Services\HR;

use App\Models\RecruitmentApplicant;
use App\Models\RecruitmentVacancy;
use App\Services\CareerAiService;
use Illuminate\Support\Str;

/**
 * Screens applicants against a vacancy's criteria with the platform's active
 * AI integration (Settings > AI). When AI is unavailable or answers badly, a
 * transparent rule-based score (keyword, experience and education match) is
 * used instead so HR always gets a ranking.
 *
 * Personal identifiers (name, email, phone) are never sent to the AI provider.
 */
class RecruitmentScreeningService
{
    private const EDUCATION_RANK = ['none' => 0, 'certificate' => 1, 'diploma' => 2, 'bachelors' => 3, 'masters' => 4, 'phd' => 5];

    public function __construct(private readonly CareerAiService $ai)
    {
    }

    /**
     * Screen applicants of a vacancy. Returns [screened count, method used for the last one].
     *
     * @return array{screened:int, ai:int, rules:int}
     */
    public function screenVacancy(RecruitmentVacancy $vacancy, bool $rescreen = false, ?int $userId = null, int $limit = 40): array
    {
        $query = $vacancy->applicants()->whereNotIn('status', ['rejected', 'withdrawn', 'hired']);
        if (! $rescreen) {
            $query->whereNull('ai_screened_at');
        }

        $result = ['screened' => 0, 'ai' => 0, 'rules' => 0];
        foreach ($query->orderBy('id')->limit($limit)->get() as $applicant) {
            $applicant->setRelation('vacancy', $vacancy);
            $method = $this->screen($applicant, $userId);
            $result['screened']++;
            $result[$method]++;
        }

        return $result;
    }

    /** Screen one applicant and store the result. Returns 'ai' or 'rules'. */
    public function screen(RecruitmentApplicant $applicant, ?int $userId = null): string
    {
        $vacancy = $applicant->vacancy;
        $criteria = $this->criteriaFor($vacancy);

        $assessment = null;
        $method = 'rules';
        try {
            $assessment = $this->aiAssessment($vacancy, $applicant, $criteria, $userId);
            $method = 'ai';
        } catch (\Throwable $e) {
            if (! ($e instanceof \RuntimeException && $e->getMessage() === 'AI_UNAVAILABLE')) {
                report($e);
            }
        }

        $assessment ??= $this->ruleAssessment($vacancy, $applicant, $criteria);
        $score = $this->weightedScore($criteria, $assessment['criteria']);

        // A must-have criterion that is clearly not met caps the recommendation.
        $missedMustHave = collect($criteria)->contains(function ($c) use ($assessment) {
            $row = collect($assessment['criteria'])->firstWhere('label', $c['label']);

            return $c['must_have'] && ($row['score'] ?? 0) < 40;
        });

        $applicant->forceFill([
            'ai_score' => $score,
            'ai_recommendation' => $missedMustHave ? 'weak' : $this->recommendationFor($score),
            'ai_summary' => Str::limit(trim((string) $assessment['summary']), 1500),
            'ai_breakdown' => [
                'criteria' => $assessment['criteria'],
                'strengths' => array_slice($assessment['strengths'], 0, 6),
                'gaps' => array_slice($assessment['gaps'], 0, 6),
                'missed_must_have' => $missedMustHave,
            ],
            'ai_method' => $method,
            'ai_screened_at' => now(),
            'status' => in_array($applicant->status, [null, 'new'], true) ? 'screened' : $applicant->status,
        ])->save();

        return $method;
    }

    public function recommendationFor(float $score): string
    {
        return match (true) {
            $score >= 80 => 'strong',
            $score >= 65 => 'good',
            $score >= 50 => 'possible',
            default => 'weak',
        };
    }

    /** Vacancy criteria plus the built-in experience / education / skills checks. */
    public function criteriaFor(RecruitmentVacancy $vacancy): array
    {
        $criteria = $vacancy->screeningCriteria();

        if ($vacancy->min_experience_years) {
            $criteria[] = ['label' => "At least {$vacancy->min_experience_years} years of relevant experience", 'weight' => 3, 'must_have' => false, 'builtin' => 'experience'];
        }
        if ($vacancy->min_education && $vacancy->min_education !== 'none') {
            $criteria[] = ['label' => 'Minimum education: '.(RecruitmentVacancy::EDUCATION_LEVELS[$vacancy->min_education] ?? $vacancy->min_education), 'weight' => 2, 'must_have' => false, 'builtin' => 'education'];
        }
        if (! empty($vacancy->skills)) {
            $criteria[] = ['label' => 'Key skills: '.implode(', ', $vacancy->skills), 'weight' => 3, 'must_have' => false, 'builtin' => 'skills'];
        }
        if ($criteria === []) {
            $criteria[] = ['label' => 'Overall fit with the job description and requirements', 'weight' => 1, 'must_have' => false];
        }

        return $criteria;
    }

    private function aiAssessment(RecruitmentVacancy $vacancy, RecruitmentApplicant $applicant, array $criteria, ?int $userId): array
    {
        $system = 'You are a fair, evidence-based recruitment screener for a non-profit organisation. '
            .'Score the candidate ONLY on job-related evidence in the application. Ignore and never infer gender, age, '
            .'ethnicity, religion, marital status, disability or any other protected characteristic. '
            .'If evidence for a criterion is missing, score it low and say so. '
            .'Reply with JSON only, no prose and no code fences.';

        $criteriaList = collect($criteria)->values()
            ->map(fn ($c, $i) => ($i + 1).'. '.$c['label'].($c['must_have'] ? ' (MUST HAVE)' : '').' [weight '.$c['weight'].']')
            ->implode("\n");

        $answers = collect($applicant->answers ?? [])
            ->map(fn ($a) => '- '.($a['question'] ?? '').': '.($a['answer'] ?? ''))->implode("\n");

        $user = "JOB: {$vacancy->title}\n"
            .($vacancy->summary ? "SUMMARY: {$vacancy->summary}\n" : '')
            .'RESPONSIBILITIES: '.Str::limit(strip_tags((string) $vacancy->responsibilities), 2500)."\n"
            .'REQUIREMENTS: '.Str::limit(strip_tags((string) $vacancy->requirements), 2500)."\n\n"
            ."SCREENING CRITERIA:\n{$criteriaList}\n\n"
            ."CANDIDATE (identifiers removed):\n"
            .'Stated education: '.(RecruitmentVacancy::EDUCATION_LEVELS[$applicant->education_level] ?? ($applicant->education_level ?: 'not stated'))."\n"
            .'Stated years of experience: '.($applicant->years_experience ?? 'not stated')."\n"
            .($answers ? "Application answers:\n{$answers}\n" : '')
            .'Cover letter: '.Str::limit($this->redact((string) $applicant->cover_letter, $applicant), 3000)."\n"
            .'CV text: '.Str::limit($this->redact((string) $applicant->cv_text, $applicant), 9000)."\n\n"
            .'Return JSON exactly in this shape: {"criteria":[{"label":"<criterion text as given>","score":0-100,"note":"<one short evidence-based sentence>"}],'
            .'"strengths":["..."],"gaps":["..."],"summary":"<2-3 sentence overall assessment for HR>"}';

        $response = $this->ai->generate('recruitment_screening', $system, $user, $userId);
        $json = $this->decodeJson((string) ($response['text'] ?? ''));
        if (! is_array($json) || ! is_array($json['criteria'] ?? null)) {
            throw new \UnexpectedValueException('AI screening returned an unreadable answer.');
        }

        // Match the AI rows back to our criteria by position first, then by label.
        $rows = array_values($json['criteria']);
        $scored = [];
        foreach (array_values($criteria) as $i => $c) {
            $row = $rows[$i] ?? collect($rows)->first(fn ($r) => Str::lower(trim((string) ($r['label'] ?? ''))) === Str::lower($c['label']));
            $scored[] = [
                'label' => $c['label'],
                'score' => max(0, min(100, (int) round((float) ($row['score'] ?? 0)))),
                'note' => Str::limit(trim((string) ($row['note'] ?? 'No evidence found.')), 300),
            ];
        }

        return [
            'criteria' => $scored,
            'strengths' => $this->stringList($json['strengths'] ?? []),
            'gaps' => $this->stringList($json['gaps'] ?? []),
            'summary' => (string) ($json['summary'] ?? ''),
        ];
    }

    /** Transparent fallback: keyword coverage, years of experience and education level. */
    public function ruleAssessment(RecruitmentVacancy $vacancy, RecruitmentApplicant $applicant, array $criteria): array
    {
        $haystack = Str::lower(implode("\n", [
            (string) $applicant->cv_text,
            (string) $applicant->cover_letter,
            collect($applicant->answers ?? [])->pluck('answer')->implode("\n"),
        ]));

        $rows = [];
        $strengths = [];
        $gaps = [];
        foreach ($criteria as $c) {
            [$score, $note] = match ($c['builtin'] ?? null) {
                'experience' => $this->experienceScore($applicant, (int) $vacancy->min_experience_years),
                'education' => $this->educationScore($applicant, (string) $vacancy->min_education),
                'skills' => $this->keywordScore($haystack, $vacancy->skills ?? []),
                default => $this->keywordScore($haystack, $this->keywords($c['label'])),
            };
            $rows[] = ['label' => $c['label'], 'score' => $score, 'note' => $note];
            if ($score >= 70) $strengths[] = $c['label'];
            if ($score < 40) $gaps[] = $c['label'];
        }

        $hasCv = filled($applicant->cv_text);

        return [
            'criteria' => $rows,
            'strengths' => $strengths,
            'gaps' => $gaps,
            'summary' => 'Rule-based screen (AI was unavailable): scored on keyword matches in the '
                .($hasCv ? 'CV and cover letter' : 'cover letter (no readable CV text)')
                .', stated experience and education. Review the application before deciding.',
        ];
    }

    private function experienceScore(RecruitmentApplicant $applicant, int $required): array
    {
        $years = $applicant->years_experience;
        if ($years === null) return [30, 'Years of experience not stated.'];
        if ($required <= 0) return [80, "{$years} years stated."];
        $score = (int) min(100, round(($years / $required) * 85));

        return [$score, "{$years} of {$required} required years stated."];
    }

    private function educationScore(RecruitmentApplicant $applicant, string $required): array
    {
        $have = self::EDUCATION_RANK[$applicant->education_level] ?? null;
        $need = self::EDUCATION_RANK[$required] ?? 0;
        if ($have === null) return [30, 'Education level not stated.'];
        if ($have >= $need) return [$have > $need ? 100 : 90, 'Meets the minimum education level.'];

        return [max(10, 60 - ($need - $have) * 20), 'Below the minimum education level.'];
    }

    private function keywordScore(string $haystack, array $keywords): array
    {
        $keywords = array_values(array_unique(array_filter(array_map(fn ($k) => Str::lower(trim((string) $k)), $keywords))));
        if ($keywords === []) return [50, 'Not enough detail to match automatically.'];
        if (trim($haystack) === '') return [10, 'No CV text or cover letter to match against.'];

        $found = array_values(array_filter($keywords, fn ($k) => str_contains($haystack, $k)));
        $score = (int) round(15 + 85 * count($found) / count($keywords));

        return [$score, $found ? 'Mentions: '.Str::limit(implode(', ', $found), 200) : 'No matching terms found.'];
    }

    /** Meaningful words in a free-text criterion. */
    private function keywords(string $label): array
    {
        $stop = ['and', 'the', 'with', 'for', 'from', 'that', 'have', 'has', 'into', 'least', 'years', 'year', 'experience', 'ability',
            'able', 'strong', 'good', 'proven', 'knowledge', 'skills', 'skill', 'working', 'work', 'must', 'should', 'minimum', 'relevant', 'demonstrated', 'excellent'];
        $words = preg_split('/[^a-z0-9+#.]+/i', Str::lower($label)) ?: [];

        return array_values(array_filter($words, fn ($w) => strlen($w) > 2 && ! in_array($w, $stop, true)));
    }

    private function weightedScore(array $criteria, array $rows): float
    {
        $total = 0;
        $weights = 0;
        foreach (array_values($criteria) as $i => $c) {
            $total += ($rows[$i]['score'] ?? 0) * $c['weight'];
            $weights += $c['weight'];
        }

        return $weights ? round($total / $weights, 2) : 0.0;
    }

    private function redact(string $text, RecruitmentApplicant $applicant): string
    {
        $text = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email]', $text) ?? $text;
        $text = preg_replace('/\+?\d[\d\s().-]{7,}\d/', '[phone]', $text) ?? $text;
        foreach (preg_split('/\s+/', trim((string) $applicant->name)) ?: [] as $part) {
            if (mb_strlen($part) > 2) {
                $text = preg_replace('/\b'.preg_quote($part, '/').'\b/iu', '[candidate]', $text) ?? $text;
            }
        }

        return $text;
    }

    private function decodeJson(string $text): ?array
    {
        $text = trim(preg_replace('/^```(?:json)?|```$/m', '', $text) ?? $text);
        $decoded = json_decode($text, true);
        if (is_array($decoded)) return $decoded;

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) return null;
        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function stringList(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->map(fn ($v) => Str::limit(trim(is_string($v) ? $v : (string) json_encode($v)), 200))
            ->filter()->values()->all();
    }
}
