<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentInterview;
use App\Models\RecruitmentInterviewPanelist;
use App\Models\RecruitmentInterviewScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Staff invited to an interview panel see the candidate and score each question. */
class InterviewPanelController extends Controller
{
    public function index(Request $request)
    {
        $panels = RecruitmentInterviewPanelist::with(['interview.applicant', 'interview.vacancy'])
            ->where('user_id', $request->user()->id)
            ->whereHas('interview')
            ->get()
            ->sortBy(fn ($p) => [$p->submitted_at ? 1 : 0, $p->interview->scheduled_at])
            ->values();

        return view('staff.interviews.index', ['panels' => $panels]);
    }

    public function show(Request $request, RecruitmentInterview $interview)
    {
        $panelist = $this->panelist($request, $interview);
        $interview->load(['applicant', 'vacancy.questions', 'panelists.user']);

        return view('staff.interviews.show', [
            'interview' => $interview,
            'panelist' => $panelist->load('scores'),
            'scores' => $panelist->scores->keyBy('question_id'),
        ]);
    }

    public function score(Request $request, RecruitmentInterview $interview)
    {
        $panelist = $this->panelist($request, $interview);
        abort_if($interview->status === 'cancelled', 422, 'This interview was cancelled.');
        $questions = $interview->vacancy->questions;

        $rules = [
            'recommendation' => [$request->boolean('submit') ? 'required' : 'nullable', Rule::in(array_keys(RecruitmentInterviewPanelist::RECOMMENDATIONS))],
            'overall_comment' => ['nullable', 'string', 'max:5000'],
            'comments' => ['nullable', 'array'],
            'comments.*' => ['nullable', 'string', 'max:2000'],
        ];
        foreach ($questions as $q) {
            $rules["scores.{$q->id}"] = [$request->boolean('submit') ? 'required' : 'nullable', 'integer', 'min:0', 'max:'.$q->max_score];
        }
        $data = $request->validate($rules, ['scores.*.required' => 'Score every question before submitting.']);

        abort_if($panelist->submitted_at && ! $request->user()->hasPermission('hr.manage'), 422, 'Your scores were already submitted.');

        DB::transaction(function () use ($questions, $data, $panelist, $request, $interview) {
            $earned = 0;
            $possible = 0;
            foreach ($questions as $q) {
                $score = $data['scores'][$q->id] ?? null;
                RecruitmentInterviewScore::updateOrCreate(
                    ['panelist_id' => $panelist->id, 'question_id' => $q->id],
                    ['score' => $score, 'comment' => $data['comments'][$q->id] ?? null],
                );
                $possible += $q->max_score * $q->weight;
                $earned += ((int) $score) * $q->weight;
            }

            $panelist->fill([
                'recommendation' => $data['recommendation'] ?? $panelist->recommendation,
                'overall_comment' => $data['overall_comment'] ?? null,
                'total_score' => $possible ? round($earned / $possible * 100, 2) : null,
            ]);
            if ($request->boolean('submit')) {
                $panelist->submitted_at = now();
            }
            $panelist->save();
            $interview->refreshAverage();

            if ($request->boolean('submit') && $interview->panelists()->whereNull('submitted_at')->doesntExist()) {
                $interview->update(['status' => 'completed']);
            }
        });

        return redirect()->route('staff.interviews.show', $interview)
            ->with('success', $request->boolean('submit') ? 'Scores submitted to HR. Thank you.' : 'Draft scores saved.');
    }

    public function cv(Request $request, RecruitmentInterview $interview)
    {
        $this->panelist($request, $interview);
        $applicant = $interview->applicant;
        abort_unless($applicant->cv_path && Storage::disk('local')->exists($applicant->cv_path), 404);

        return Storage::disk('local')->download($applicant->cv_path, $applicant->cv_original_name ?: 'cv');
    }

    private function panelist(Request $request, RecruitmentInterview $interview): RecruitmentInterviewPanelist
    {
        $panelist = $interview->panelists()->where('user_id', $request->user()->id)->first();
        abort_unless($panelist, 403, 'You are not on this interview panel.');

        return $panelist;
    }
}
