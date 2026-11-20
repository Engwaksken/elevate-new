<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\SystemMail;
use App\Models\RecruitmentApplicant;
use App\Models\RecruitmentVacancy;
use App\Models\User;
use App\Services\DocumentTextExtractor;
use App\Services\HR\RecruitmentScreeningService;
use App\Services\UserNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;

/**
 * Public careers page and the embeddable job widget other websites can host
 * (an iframe injected by /careers/embed.js). Applicants do not need an account.
 */
class CareersController extends Controller
{
    public function index(Request $request)
    {
        return view('careers.index', ['vacancies' => $this->openVacancies($request), 'embed' => false]);
    }

    public function show(string $slug)
    {
        return view('careers.show', ['vacancy' => $this->vacancy($slug), 'embed' => false]);
    }

    public function embed(Request $request)
    {
        if ($slug = $request->get('job')) {
            return view('careers.show', ['vacancy' => $this->vacancy($slug), 'embed' => true]);
        }

        return view('careers.index', ['vacancies' => $this->openVacancies($request), 'embed' => true]);
    }

    public function embedShow(string $slug)
    {
        return view('careers.show', ['vacancy' => $this->vacancy($slug), 'embed' => true]);
    }

    /** Loader script for other websites: injects an auto-resizing iframe. */
    public function script()
    {
        return response()->view('careers.embed-script', ['base' => url('/careers/embed')])
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function apply(Request $request, string $slug, DocumentTextExtractor $extractor)
    {
        $vacancy = $this->vacancy($slug);
        $embed = $request->boolean('embed');

        // Spam traps: a hidden field bots fill in, and forms submitted impossibly fast.
        if (filled($request->input('website')) || (int) $request->input('started') > time() - 3) {
            return $this->thanks($vacancy, $embed, null);
        }

        if (! $vacancy->isAcceptingApplications()) {
            return $this->formWithErrors($request, $vacancy, $embed, new MessageBag(['email' => 'This vacancy is no longer accepting applications.']));
        }

        $questions = $vacancy->application_questions ?? [];
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('recruitment_applicants', 'email')->where('vacancy_id', $vacancy->id)],
            'phone' => ['required', 'string', 'max:60'],
            'location' => ['nullable', 'string', 'max:190'],
            'education_level' => ['required', Rule::in(array_keys(RecruitmentVacancy::EDUCATION_LEVELS))],
            'years_experience' => ['required', 'integer', 'min:0', 'max:60'],
            'linkedin_url' => ['nullable', 'url', 'max:300'],
            'cover_letter' => ['nullable', 'string', 'max:8000'],
            'answers' => [$questions ? 'required' : 'nullable', 'array'],
            'answers.*' => ['required', 'string', 'max:3000'],
            'cv' => [$vacancy->cv_required ? 'required' : 'nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'consent' => ['accepted'],
        ], [
            'email.unique' => 'You have already applied for this position with this email address.',
            'consent.accepted' => 'Please agree to the processing of your application data.',
            'answers.*.required' => 'Please answer every question.',
        ]);

        // Render errors directly: an embedded (cross-site) iframe may not keep the session for a redirect.
        if ($validator->fails()) {
            return $this->formWithErrors($request, $vacancy, $embed, $validator->errors());
        }
        $data = $validator->validated();

        $cvPath = null;
        $cvText = null;
        if ($file = $request->file('cv')) {
            $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');
            $cvPath = $file->storeAs('recruitment/cvs/'.$vacancy->id, Str::uuid().'.'.$ext, 'local');
            try {
                $cvText = Str::limit($extractor->extract($file->getRealPath(), $ext), 60000, '');
            } catch (\Throwable $e) {
                $cvText = null; // scanned or unreadable CVs are still accepted
            }
        }

        $applicant = RecruitmentApplicant::create([
            'vacancy_id' => $vacancy->id,
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'phone' => $data['phone'],
            'location' => $data['location'] ?? null,
            'education_level' => $data['education_level'],
            'years_experience' => $data['years_experience'],
            'linkedin_url' => $data['linkedin_url'] ?? null,
            'cover_letter' => $data['cover_letter'] ?? null,
            'answers' => collect($questions)->values()->map(fn ($q, $i) => ['question' => $q, 'answer' => $data['answers'][$i] ?? ''])->all() ?: null,
            'cv_path' => $cvPath,
            'cv_original_name' => $file?->getClientOriginalName(),
            'cv_text' => $cvText,
            'source' => $embed ? 'embed' : 'careers',
            'source_site' => $embed ? Str::limit((string) $request->input('host'), 250, '') ?: null : null,
        ]);

        $this->confirm($applicant);
        $this->notifyHr($applicant);

        // Score against the job criteria once the applicant has their confirmation page.
        $applicantId = $applicant->id;
        dispatch(function () use ($applicantId) {
            $applicant = RecruitmentApplicant::with('vacancy')->find($applicantId);
            if ($applicant && ! $applicant->ai_screened_at) {
                app(RecruitmentScreeningService::class)->screen($applicant);
            }
        })->afterResponse();

        return $this->thanks($vacancy, $embed, $applicant);
    }

    private function formWithErrors(Request $request, RecruitmentVacancy $vacancy, bool $embed, MessageBag $errors)
    {
        return response()->view('careers.show', [
            'vacancy' => $vacancy,
            'embed' => $embed,
            'input' => $request->except(['cv', 'website', 'started']),
            'errors' => (new ViewErrorBag())->put('default', $errors),
        ], 422);
    }

    private function thanks(RecruitmentVacancy $vacancy, bool $embed, ?RecruitmentApplicant $applicant)
    {
        return view('careers.thanks', ['vacancy' => $vacancy, 'embed' => $embed, 'applicant' => $applicant]);
    }

    private function confirm(RecruitmentApplicant $applicant): void
    {
        try {
            Mail::to($applicant->email)->send(new SystemMail(
                subjectLine: 'Application received: '.$applicant->vacancy->title,
                heading: 'We have received your application',
                lines: [
                    'Thank you for applying for the '.$applicant->vacancy->title.' position.',
                    'Your application reference is '.$applicant->reference.'. Our HR team will review all applications'
                        .($applicant->vacancy->closing_date ? ' after the closing date ('.$applicant->vacancy->closing_date->format('d M Y').')' : '')
                        .' and contact shortlisted candidates.',
                ],
                actionUrl: route('careers.index'),
                actionLabel: 'View other openings',
                greeting: 'Dear '.strtok($applicant->name, ' '),
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function notifyHr(RecruitmentApplicant $applicant): void
    {
        try {
            $owner = $applicant->vacancy->created_by ? User::find($applicant->vacancy->created_by) : null;
            if ($owner) {
                app(UserNotificationService::class)->send($owner, 'recruitment_application', 'New application: '.$applicant->vacancy->title,
                    $applicant->name.' applied ('.$applicant->reference.').', route('admin.hr.recruitment.applicant', $applicant));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function openVacancies(Request $request)
    {
        return RecruitmentVacancy::with('department')->acceptingApplications()
            ->when($request->get('department'), fn ($q, $d) => $q->where('department_id', $d))
            ->when(trim((string) $request->get('q')), fn ($q, $s) => $q->where(fn ($w) => $w->where('title', 'like', "%{$s}%")->orWhere('location', 'like', "%{$s}%")))
            ->orderByRaw('closing_date IS NULL')->orderBy('closing_date')->latest('published_at')
            ->get();
    }

    private function vacancy(string $slug): RecruitmentVacancy
    {
        return RecruitmentVacancy::with('department')->where('slug', $slug)->whereIn('status', ['open', 'closed', 'filled'])->firstOrFail();
    }
}
