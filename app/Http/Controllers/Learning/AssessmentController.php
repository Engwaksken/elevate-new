<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use App\Models\Enrolment;
use App\Exceptions\ParticipantRuleException;
use App\Services\CertificateService;
use App\Services\Learning\LearningFileService;
use App\Services\Participant\ParticipantAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentController extends Controller
{
    public function show(Assessment $assessment, ParticipantAssignmentService $assignments)
    {
        $assessment->load(['questions','course']);
        abort_unless($assessment->is_published, 404);

        $enrolled = Enrolment::where('course_id', $assessment->course_id)
            ->where('user_id', auth()->id())
            ->exists();

        // A course's entry assessment can be taken before enrolment.
        $isEntryAssessment = $assessment->course
            && (int) $assessment->course->entry_assessment_id === (int) $assessment->id;

        abort_unless($enrolled || $isEntryAssessment, 403);

        $attempts = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('user_id', auth()->id())
            ->count();

        abort_if($attempts >= $assessment->max_attempts, 403, 'Maximum attempts reached.');

        $files = app(LearningFileService::class);
        $briefFiles = $files->assessmentFiles($assessment);

        $previousAttempts = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('user_id', auth()->id())
            ->orderByDesc('attempt_number')
            ->get();
        $files->syncAttempts($previousAttempts);
        $previousAttempts->load('files');

        // Same due-date rules as the mobile app: an approved extension moves the
        // due date, and an overdue assignment can only be reopened by an extension.
        $context = $assignments->context(auth()->user(), [$assessment->id]);
        $dueState = $assignments->state($assessment, $context);
        $dueState['effective_due'] = $assignments->effectiveDueAt($assessment, $context);
        $dueState['extension'] = $context['latest'][$assessment->id] ?? null;
        $dueState['can_request_extension'] = $dueState['can_request_extension'] && $enrolled;

        return view('learning.assessments.show', compact('assessment','attempts','briefFiles','previousAttempts','dueState'));
    }

    /**
     * Ask the course instructors for more time on an assignment (web counterpart of
     * POST /api/v1/participant/assignments/{assessment}/extension-requests).
     */
    public function requestExtension(Request $request, Assessment $assessment, ParticipantAssignmentService $assignments)
    {
        abort_unless($assessment->is_published, 404);
        abort_unless(
            Enrolment::where('course_id', $assessment->course_id)->where('user_id', $request->user()->id)->exists(),
            403
        );

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'requested_due_at' => ['nullable', 'date', 'after:now'],
        ], [
            'reason.min' => 'Please explain why you need more time (at least 10 characters).',
            'requested_due_at.after' => 'The date you are asking for must be in the future.',
        ]);

        try {
            $assignments->createExtensionRequest(
                $request->user(),
                $assessment,
                trim($data['reason']),
                ! empty($data['requested_due_at']) ? Carbon::parse($data['requested_due_at']) : null
            );
        } catch (ParticipantRuleException $e) {
            return back()->withInput()->withErrors(['extension' => $e->getMessage()]);
        }

        return redirect()->route('learning.assessment.show', $assessment)
            ->with('success', 'Extension request sent to your instructor. You will be notified when it is reviewed.');
    }

    public function submit(Request $request, Assessment $assessment, CertificateService $certificateService)
    {
        $assessment->load(['questions','course']);

        abort_unless($assessment->is_published, 404);

        $attemptNumber = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('user_id', auth()->id())
            ->count() + 1;

        abort_if($attemptNumber > $assessment->max_attempts, 403);

        $assignments = app(ParticipantAssignmentService::class);
        try {
            $assignments->ensureNotOverdue($assessment, $assignments->context($request->user(), [$assessment->id]), null);
        } catch (ParticipantRuleException $e) {
            return redirect()->route('learning.assessment.show', $assessment)->withErrors(['extension' => $e->getMessage()]);
        }

        $fileService = app(LearningFileService::class);
        $data = $request->validate([
            'submission_text' => ['nullable', 'string', 'max:20000'],
            'submission_links' => ['nullable', 'string', 'max:20000'],
        ] + $fileService->submissionRules($assessment, 'submission_files'),
            $fileService->submissionMessages($assessment, 'submission_files'));
        $uploads = $fileService->uploadedFiles($request, 'submission_files');
        $links = collect(preg_split('/\\r?\\n/', (string) ($data['submission_links'] ?? '')))
            ->map(fn ($link) => trim($link))->filter()->unique()->values();
        if ($links->isNotEmpty() && ! $assessment->allow_link_submissions) {
            throw ValidationException::withMessages(['submission_links' => 'This assignment does not accept link submissions.']);
        }
        foreach ($links as $link) {
            $parts = parse_url($link);
            if (! filter_var($link, FILTER_VALIDATE_URL) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
                throw ValidationException::withMessages(['submission_links' => 'Enter valid web links beginning with https:// or http://, one per line.']);
            }
        }
        if ($assessment->requiresAttachment() && $uploads === [] && $links->isEmpty()) {
            throw ValidationException::withMessages(['submission_files' => 'Submit at least one file or web link.']);
        }

        $answers = $request->input('answers', []);

        $attempt = DB::transaction(function () use ($assessment, $answers, $attemptNumber, $data, $uploads, $links, $fileService) {
            $attempt = AssessmentAttempt::create([
                'assessment_id' => $assessment->id,
                'user_id' => auth()->id(),
                'attempt_number' => $attemptNumber,
                'submission_text' => $data['submission_text'] ?? null,
                'submission_links' => $links->all() ?: null,
                'status' => 'submitted',
                'started_at' => now(),
                'submitted_at' => now(),
            ]);

            $fileService->storeSubmissionFiles($attempt, $uploads);

            $possible = 0;
            $awarded = 0;
            $autoGradable = true;

            foreach ($assessment->questions as $question) {
                $possible += (float) $question->marks;
                $value = $answers[$question->id] ?? null;
                $marks = null;

                if (in_array($question->question_type, ['multiple_choice','true_false'], true)) {
                    $expected = data_get($question->correct_answer, 'value');
                    $marks = (string) $value === (string) $expected
                        ? (float) $question->marks
                        : 0;

                    $awarded += $marks;
                } else {
                    $autoGradable = false;
                }

                AssessmentAnswer::create([
                    'assessment_attempt_id' => $attempt->id,
                    'assessment_question_id' => $question->id,
                    'answer_text' => is_scalar($value) ? (string) $value : null,
                    'answer_json' => is_array($value) ? $value : null,
                    'awarded_marks' => $marks,
                ]);
            }

            if ($autoGradable && $possible > 0) {
                $percentage = round(($awarded / $possible) * 100, 2);

                $attempt->update([
                    'score' => $awarded,
                    'percentage' => $percentage,
                    'status' => 'graded',
                    'graded_at' => now(),
                ]);
            }

            return $attempt->fresh();
        });

        if ($attempt->status === 'graded') {
            $scores = AssessmentAttempt::where('user_id', auth()->id())
                ->where('status', 'graded')
                ->whereHas('assessment', fn ($q) => $q->where('course_id', $assessment->course_id))
                ->selectRaw('assessment_id, MAX(percentage) as best_percentage')
                ->groupBy('assessment_id')
                ->pluck('best_percentage');

            $final = $scores->count() ? round($scores->avg(), 2) : null;

            Enrolment::where('course_id', $assessment->course_id)
                ->where('user_id', auth()->id())
                ->update(['final_score' => $final]);

            $certificateService->issueIfEligible($assessment->course, auth()->user());
        }

        $enrolled = Enrolment::where('course_id', $assessment->course_id)
            ->where('user_id', auth()->id())
            ->exists();

        if (! $enrolled) {
            if ($attempt->status !== 'graded') {
                return redirect()->route('learning.course.show', $assessment->course)
                    ->with('success', 'Entry assessment submitted. It will be marked before enrolment.');
            }

            $passed = $assessment->course && $assessment->course->entryAssessmentPassedFor(auth()->id());

            return redirect()->route('learning.course.show', $assessment->course)
                ->with(
                    $passed ? 'success' : 'error',
                    $passed
                        ? 'Entry assessment passed. You can now enrol in the course.'
                        : 'Entry assessment not passed. Please review the course requirements.'
                );
        }

        return redirect()->route('learning.course.dashboard', $assessment->course)
            ->with('success', 'Assessment submitted successfully.');
    }
}
