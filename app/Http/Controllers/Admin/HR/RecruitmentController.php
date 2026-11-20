<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Mail\SystemMail;
use App\Models\Department;
use App\Models\RecruitmentApplicant;
use App\Models\RecruitmentInterviewQuestion;
use App\Models\RecruitmentVacancy;
use App\Models\User;
use App\Services\CareerAiService;
use App\Services\HR\RecruitmentScreeningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * HR / admin recruitment: post vacancies (careers page + embed widget), review
 * applicants ranked by AI screening, shortlist, and set interview questions.
 * Interviews and panels live in RecruitmentInterviewController.
 */
class RecruitmentController extends Controller
{
    public function index(Request $request)
    {
        $query = RecruitmentVacancy::with('department')
            ->withCount([
                'applicants',
                'applicants as shortlisted_count' => fn ($q) => $q->whereIn('status', ['shortlisted', 'interview', 'offered', 'hired']),
                'applicants as new_count' => fn ($q) => $q->where('status', 'new'),
            ])->latest();

        if ($search = trim((string) $request->get('search'))) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('reference', 'like', "%{$search}%")->orWhere('location', 'like', "%{$search}%"));
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return view('admin.hr.recruitment.index', [
            'vacancies' => $query->paginate(20)->withQueryString(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'stats' => [
                'open' => RecruitmentVacancy::where('status', 'open')->count(),
                'applicants' => RecruitmentApplicant::count(),
                'new' => RecruitmentApplicant::where('status', 'new')->count(),
                'shortlisted' => RecruitmentApplicant::whereIn('status', ['shortlisted', 'interview'])->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;
        if ($data['status'] === 'open') {
            $data['published_at'] = now();
        }
        $vacancy = RecruitmentVacancy::create($data);

        return redirect()->route('admin.hr.recruitment.show', $vacancy)
            ->with('success', 'Vacancy created. Add interview questions and copy the embed code from the tabs below.');
    }

    public function update(Request $request, RecruitmentVacancy $vacancy)
    {
        $data = $this->validated($request, $vacancy);
        if ($data['status'] === 'open' && ! $vacancy->published_at) {
            $data['published_at'] = now();
        }
        $vacancy->update($data);

        return back()->with('success', 'Vacancy updated.');
    }

    public function destroy(RecruitmentVacancy $vacancy)
    {
        $vacancy->delete();

        return redirect()->route('admin.hr.recruitment.index')->with('success', 'Vacancy archived.');
    }

    public function show(Request $request, RecruitmentVacancy $vacancy)
    {
        $vacancy->load(['department', 'questions', 'interviews.applicant', 'interviews.panelists.user']);

        $applicants = $vacancy->applicants()
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->when(trim((string) $request->get('search')), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhere('reference', 'like', "%{$s}%")))
            ->orderByRaw('ai_score IS NULL')->orderByDesc('ai_score')->orderBy('created_at')
            ->get();

        $counts = $vacancy->applicants()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('admin.hr.recruitment.show', [
            'vacancy' => $vacancy,
            'applicants' => $applicants,
            'counts' => $counts,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'staff' => User::where('user_type', 'staff')->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']),
            'aiReady' => (bool) optional(\App\Models\AiIntegration::activeSystem())->apiKey(),
            'tab' => $request->get('tab', 'applicants'),
        ]);
    }

    public function screen(Request $request, RecruitmentVacancy $vacancy, RecruitmentScreeningService $screening)
    {
        @set_time_limit(300);
        $result = $screening->screenVacancy($vacancy, $request->boolean('rescreen'), $request->user()->id);

        if ($result['screened'] === 0) {
            return back()->with('success', 'All applicants have already been screened. Tick "Re-screen everyone" to score them again.');
        }

        $message = "Screened {$result['screened']} applicant(s)";
        $message .= $result['rules'] ? " ({$result['ai']} by AI, {$result['rules']} by rule-based scoring because AI was unavailable)." : ' with AI.';
        $remaining = $vacancy->applicants()->whereNull('ai_screened_at')->whereNotIn('status', ['rejected', 'withdrawn', 'hired'])->count();
        if ($remaining && ! $request->boolean('rescreen')) {
            $message .= " {$remaining} remaining - run screening again to continue.";
        }

        return back()->with('success', $message);
    }

    public function screenOne(Request $request, RecruitmentApplicant $applicant, RecruitmentScreeningService $screening)
    {
        $method = $screening->screen($applicant->load('vacancy'), $request->user()->id);

        return back()->with('success', $method === 'ai' ? 'Applicant screened with AI.' : 'AI was unavailable; applicant scored with rule-based screening.');
    }

    /** Shortlist the top N screened applicants by score. */
    public function shortlistTop(Request $request, RecruitmentVacancy $vacancy)
    {
        $data = $request->validate([
            'top' => ['required', 'integer', 'min:1', 'max:100'],
            'min_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $ids = $vacancy->applicants()->whereIn('status', ['new', 'screened'])->whereNotNull('ai_score')
            ->when(isset($data['min_score']), fn ($q) => $q->where('ai_score', '>=', $data['min_score']))
            ->orderByDesc('ai_score')->limit($data['top'])->pluck('id');

        $count = RecruitmentApplicant::whereIn('id', $ids)->update([
            'status' => 'shortlisted', 'shortlisted_by' => $request->user()->id, 'shortlisted_at' => now(),
        ]);

        return back()->with('success', $count ? "{$count} top applicant(s) shortlisted." : 'No screened applicants matched. Run AI screening first.');
    }

    public function bulk(Request $request, RecruitmentVacancy $vacancy, RecruitmentScreeningService $screening)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(['shortlist', 'reject', 'screen', 'reset'])],
            'notify' => ['nullable', 'boolean'],
        ]);
        $applicants = $vacancy->applicants()->whereIn('id', $data['ids'])->get();

        foreach ($applicants as $applicant) {
            match ($data['action']) {
                'shortlist' => $applicant->update(['status' => 'shortlisted', 'shortlisted_by' => $request->user()->id, 'shortlisted_at' => now()]),
                'reject' => $this->reject($applicant, $request->boolean('notify')),
                'reset' => $applicant->update(['status' => $applicant->ai_screened_at ? 'screened' : 'new', 'shortlisted_at' => null, 'shortlisted_by' => null]),
                'screen' => $screening->screen($applicant->setRelation('vacancy', $vacancy), $request->user()->id),
            };
        }

        return back()->with('success', $applicants->count().' applicant(s) updated.');
    }

    public function applicant(RecruitmentApplicant $applicant)
    {
        $applicant->load(['vacancy.questions', 'interviews.panelists.user', 'interviews.panelists.scores', 'shortlister']);

        return view('admin.hr.recruitment.applicant', [
            'applicant' => $applicant,
            'vacancy' => $applicant->vacancy,
            'staff' => User::where('user_type', 'staff')->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function updateApplicant(Request $request, RecruitmentApplicant $applicant)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(RecruitmentApplicant::STATUSES)],
            'hr_notes' => ['nullable', 'string', 'max:5000'],
            'notify' => ['nullable', 'boolean'],
        ]);

        if ($data['status'] === 'rejected' && $applicant->status !== 'rejected') {
            $this->reject($applicant, $request->boolean('notify'));
        }
        if ($data['status'] === 'shortlisted' && ! $applicant->shortlisted_at) {
            $applicant->forceFill(['shortlisted_by' => $request->user()->id, 'shortlisted_at' => now()]);
        }
        $applicant->fill(['status' => $data['status'], 'hr_notes' => $data['hr_notes'] ?? $applicant->hr_notes])->save();

        return back()->with('success', 'Applicant updated.');
    }

    public function cv(RecruitmentApplicant $applicant)
    {
        abort_unless($applicant->cv_path && Storage::disk('local')->exists($applicant->cv_path), 404);

        return Storage::disk('local')->download($applicant->cv_path, $applicant->cv_original_name ?: 'cv');
    }

    public function storeQuestion(Request $request, RecruitmentVacancy $vacancy)
    {
        $data = $this->validatedQuestion($request);
        $data['position'] = (int) $vacancy->questions()->max('position') + 1;
        $vacancy->questions()->create($data);

        return redirect()->route('admin.hr.recruitment.show', [$vacancy, 'tab' => 'questions'])->with('success', 'Interview question added.');
    }

    public function updateQuestion(Request $request, RecruitmentInterviewQuestion $question)
    {
        $question->update($this->validatedQuestion($request));

        return redirect()->route('admin.hr.recruitment.show', [$question->vacancy_id, 'tab' => 'questions'])->with('success', 'Question updated.');
    }

    public function destroyQuestion(RecruitmentInterviewQuestion $question)
    {
        $vacancyId = $question->vacancy_id;
        $question->delete();

        return redirect()->route('admin.hr.recruitment.show', [$vacancyId, 'tab' => 'questions'])->with('success', 'Question removed.');
    }

    /** Draft interview questions from the job description with the active AI. */
    public function suggestQuestions(Request $request, RecruitmentVacancy $vacancy, CareerAiService $ai)
    {
        $system = 'You are an experienced HR interviewer. Write fair, competency-based interview questions. Reply with JSON only.';
        $user = "Job: {$vacancy->title}\nResponsibilities: ".Str::limit(strip_tags((string) $vacancy->responsibilities), 2500)
            ."\nRequirements: ".Str::limit(strip_tags((string) $vacancy->requirements), 2500)
            ."\nCriteria: ".collect($vacancy->screeningCriteria())->pluck('label')->implode('; ')
            ."\n\nWrite 6 interview questions. Return: {\"questions\":[{\"question\":\"...\",\"guidance\":\"what a strong answer includes\"}]}";

        try {
            $text = (string) ($ai->generate('recruitment_questions', $system, $user, $request->user()->id)['text'] ?? '');
            $start = strpos($text, '{');
            $json = json_decode($start === false ? '' : substr($text, $start, strrpos($text, '}') - $start + 1), true);
            $questions = collect($json['questions'] ?? [])->filter(fn ($q) => filled($q['question'] ?? null))->take(10);
        } catch (\Throwable $e) {
            $questions = collect();
        }

        if ($questions->isEmpty()) {
            return redirect()->route('admin.hr.recruitment.show', [$vacancy, 'tab' => 'questions'])
                ->with('error', 'AI could not suggest questions right now. Check Settings > AI, or add questions manually.');
        }

        $position = (int) $vacancy->questions()->max('position');
        foreach ($questions as $q) {
            $vacancy->questions()->create([
                'question' => Str::limit(trim((string) $q['question']), 1000),
                'guidance' => Str::limit(trim((string) ($q['guidance'] ?? '')), 1000) ?: null,
                'max_score' => 5, 'weight' => 1, 'position' => ++$position,
            ]);
        }

        return redirect()->route('admin.hr.recruitment.show', [$vacancy, 'tab' => 'questions'])
            ->with('success', $questions->count().' AI-suggested questions added. Review and edit them before interviews.');
    }

    private function reject(RecruitmentApplicant $applicant, bool $notify): void
    {
        $applicant->update(['status' => 'rejected']);
        if (! $notify || blank($applicant->email)) return;

        try {
            Mail::to($applicant->email)->send(new SystemMail(
                subjectLine: 'Your application for '.$applicant->vacancy->title,
                heading: 'Thank you for applying',
                lines: [
                    'Thank you for your interest in the '.$applicant->vacancy->title.' role and for the time you took to apply.',
                    'After careful review we will not be taking your application further on this occasion. We encourage you to apply for future opportunities.',
                ],
                actionUrl: route('careers.index'),
                actionLabel: 'View open positions',
                greeting: 'Dear '.strtok($applicant->name, ' '),
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function validatedQuestion(Request $request): array
    {
        return $request->validate([
            'question' => ['required', 'string', 'max:1000'],
            'guidance' => ['nullable', 'string', 'max:1000'],
            'max_score' => ['required', 'integer', 'min:1', 'max:10'],
            'weight' => ['required', 'integer', 'min:1', 'max:10'],
        ]);
    }

    private function validated(Request $request, ?RecruitmentVacancy $vacancy = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'reference' => ['nullable', 'string', 'max:40'],
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
            'location' => ['nullable', 'string', 'max:190'],
            'employment_type' => ['nullable', 'string', 'max:60'],
            'salary_range' => ['nullable', 'string', 'max:120'],
            'positions' => ['nullable', 'integer', 'min:1', 'max:500'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:20000'],
            'responsibilities' => ['nullable', 'string', 'max:20000'],
            'requirements' => ['nullable', 'string', 'max:20000'],
            'min_education' => ['nullable', Rule::in(array_keys(RecruitmentVacancy::EDUCATION_LEVELS))],
            'min_experience_years' => ['nullable', 'integer', 'min:0', 'max:40'],
            'skills' => ['nullable', 'string', 'max:2000'],
            'criteria' => ['nullable', 'array', 'max:20'],
            'criteria.*.label' => ['nullable', 'string', 'max:300'],
            'criteria.*.weight' => ['nullable', 'integer', 'min:1', 'max:10'],
            'criteria.*.must_have' => ['nullable'],
            'application_questions' => ['nullable', 'string', 'max:3000'],
            'closing_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(RecruitmentVacancy::STATUSES)],
            'cv_required' => ['nullable', 'boolean'],
        ]);

        $data['skills'] = collect(preg_split('/[,\n]+/', (string) ($data['skills'] ?? '')))->map(fn ($s) => trim($s))->filter()->unique()->values()->all();
        $data['criteria'] = collect($data['criteria'] ?? [])
            ->filter(fn ($c) => filled($c['label'] ?? null))
            ->map(fn ($c) => ['label' => trim($c['label']), 'weight' => (int) ($c['weight'] ?? 1) ?: 1, 'must_have' => ! empty($c['must_have'])])
            ->values()->all();
        $data['application_questions'] = collect(preg_split('/\n+/', (string) ($data['application_questions'] ?? '')))->map(fn ($s) => trim($s))->filter()->values()->all();
        $data['positions'] = $data['positions'] ?? 1;
        $data['cv_required'] = $request->boolean('cv_required');

        return $data;
    }
}
