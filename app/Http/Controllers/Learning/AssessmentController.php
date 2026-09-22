<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use App\Models\Enrolment;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    public function show(Assessment $assessment)
    {
        $assessment->load(['questions','course']);
        abort_unless($assessment->is_published, 404);

        abort_unless(
            Enrolment::where('course_id', $assessment->course_id)
                ->where('user_id', auth()->id())
                ->exists(),
            403
        );

        $attempts = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('user_id', auth()->id())
            ->count();

        abort_if($attempts >= $assessment->max_attempts, 403, 'Maximum attempts reached.');

        return view('learning.assessments.show', compact('assessment','attempts'));
    }

    public function submit(Request $request, Assessment $assessment, CertificateService $certificateService)
    {
        $assessment->load(['questions','course']);

        abort_unless($assessment->is_published, 404);

        $attemptNumber = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('user_id', auth()->id())
            ->count() + 1;

        abort_if($attemptNumber > $assessment->max_attempts, 403);

        $answers = $request->input('answers', []);

        $attempt = DB::transaction(function () use ($assessment, $answers, $attemptNumber) {
            $attempt = AssessmentAttempt::create([
                'assessment_id' => $assessment->id,
                'user_id' => auth()->id(),
                'attempt_number' => $attemptNumber,
                'status' => 'submitted',
                'started_at' => now(),
                'submitted_at' => now(),
            ]);

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

        return redirect()->route('learning.course.show', $assessment->course)
            ->with('success', 'Assessment submitted successfully.');
    }
}
