<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Mail\SystemMail;
use App\Models\RecruitmentApplicant;
use App\Models\RecruitmentInterview;
use App\Models\RecruitmentVacancy;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Schedule interviews for shortlisted applicants, invite the candidate by
 * email and invite staff to the panel. Panel members score each vacancy
 * question in Staff\InterviewPanelController.
 */
class RecruitmentInterviewController extends Controller
{
    public function __construct(private readonly NotificationDispatcher $notifier)
    {
    }

    /** Schedule one or many applicants (one interview each, same panel). */
    public function store(Request $request, RecruitmentVacancy $vacancy)
    {
        $data = $request->validate([
            'applicant_ids' => ['required', 'array', 'min:1'],
            'applicant_ids.*' => ['integer', Rule::exists('recruitment_applicants', 'id')->where('vacancy_id', $vacancy->id)],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'slot_minutes' => ['nullable', 'integer', 'min:0', 'max:480'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:480'],
            'mode' => ['required', Rule::in(array_keys(RecruitmentInterview::MODES))],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:500'],
            'candidate_message' => ['nullable', 'string', 'max:3000'],
            'panel_ids' => ['required', 'array', 'min:1'],
            'panel_ids.*' => ['integer', Rule::exists('users', 'id')->where('user_type', 'staff')],
            'chair_id' => ['nullable', 'integer'],
            'notify_candidate' => ['nullable', 'boolean'],
        ], [
            'panel_ids.required' => 'Invite at least one staff member to the interview panel.',
        ]);

        if ($vacancy->questions()->doesntExist()) {
            return back()->withErrors(['panel_ids' => 'Add interview questions for this vacancy first, so the panel has something to score.'])->withInput();
        }

        $start = \Illuminate\Support\Carbon::parse($data['scheduled_at']);
        $gap = (int) ($data['slot_minutes'] ?? $data['duration_minutes']);
        $panel = User::whereIn('id', $data['panel_ids'])->get();
        $scheduled = 0;

        foreach (array_values(array_unique($data['applicant_ids'])) as $i => $applicantId) {
            $applicant = RecruitmentApplicant::find($applicantId);
            $interview = RecruitmentInterview::create([
                'applicant_id' => $applicant->id,
                'vacancy_id' => $vacancy->id,
                'scheduled_at' => $start->copy()->addMinutes($gap * $i),
                'duration_minutes' => $data['duration_minutes'],
                'mode' => $data['mode'],
                'location' => $data['location'] ?? null,
                'meeting_link' => $data['meeting_link'] ?? null,
                'candidate_message' => $data['candidate_message'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($panel as $member) {
                $interview->panelists()->create(['user_id' => $member->id, 'is_chair' => (int) ($data['chair_id'] ?? 0) === $member->id]);
            }

            $applicant->update(['status' => 'interview']);
            if ($request->boolean('notify_candidate')) {
                $this->emailCandidate($interview->load('applicant', 'vacancy'));
            }
            $scheduled++;
        }

        // One notice per panel member covering all their new interviews.
        foreach ($panel as $member) {
            $this->notifier->notify(
                $member,
                'recruitment_panel_invite',
                'Interview panel: '.$vacancy->title,
                "You have been invited to interview {$scheduled} candidate(s) for {$vacancy->title} starting "
                    .$start->format('D d M Y, H:i').' ('.RecruitmentInterview::MODES[$data['mode']].'). '
                    .'Open the interview to see the CV and score each question.',
                route('staff.interviews.index'),
            );
        }

        return redirect()->route('admin.hr.recruitment.show', [$vacancy, 'tab' => 'interviews'])
            ->with('success', "{$scheduled} interview(s) scheduled and ".$panel->count().' panel member(s) invited.');
    }

    public function update(Request $request, RecruitmentInterview $interview)
    {
        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:480'],
            'mode' => ['required', Rule::in(array_keys(RecruitmentInterview::MODES))],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:500'],
            'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'add_panel_ids' => ['nullable', 'array'],
            'add_panel_ids.*' => ['integer', Rule::exists('users', 'id')->where('user_type', 'staff')],
            'notify' => ['nullable', 'boolean'],
        ]);

        $rescheduled = ! $interview->scheduled_at->equalTo(\Illuminate\Support\Carbon::parse($data['scheduled_at']));
        $interview->update(collect($data)->except(['add_panel_ids', 'notify'])->all());

        foreach (User::whereIn('id', $data['add_panel_ids'] ?? [])->get() as $member) {
            $panelist = $interview->panelists()->firstOrCreate(['user_id' => $member->id]);
            if ($panelist->wasRecentlyCreated) {
                $this->notifier->notify($member, 'recruitment_panel_invite', 'Interview panel: '.$interview->vacancy->title,
                    'You have been added to the interview panel for '.$interview->applicant->name.' on '.$interview->scheduled_at->format('D d M Y, H:i').'.',
                    route('staff.interviews.show', $interview));
            }
        }

        if ($request->boolean('notify') && ($rescheduled || $data['status'] === 'cancelled')) {
            $interview->load('applicant', 'vacancy', 'panelists.user');
            $data['status'] === 'cancelled' ? $this->emailCandidateCancelled($interview) : $this->emailCandidate($interview, true);
            foreach ($interview->panelists as $p) {
                if (! $p->user) continue;
                $this->notifier->notify($p->user, 'recruitment_panel_update',
                    ($data['status'] === 'cancelled' ? 'Interview cancelled: ' : 'Interview rescheduled: ').$interview->vacancy->title,
                    $interview->applicant->name.' - '.($data['status'] === 'cancelled' ? 'cancelled.' : 'now '.$interview->scheduled_at->format('D d M Y, H:i').'.'),
                    route('staff.interviews.index'));
            }
        }

        return back()->with('success', 'Interview updated.');
    }

    public function remind(RecruitmentInterview $interview)
    {
        $pending = $interview->panelists()->whereNull('submitted_at')->with('user')->get();
        foreach ($pending as $p) {
            if ($p->user) {
                $this->notifier->notify($p->user, 'recruitment_panel_reminder', 'Please submit interview scores',
                    'Your scores for '.$interview->applicant->name.' ('.$interview->vacancy->title.') are still outstanding.',
                    route('staff.interviews.show', $interview));
            }
        }

        return back()->with('success', $pending->count().' panel member(s) reminded.');
    }

    private function emailCandidate(RecruitmentInterview $interview, bool $rescheduled = false): void
    {
        $applicant = $interview->applicant;
        $where = match ($interview->mode) {
            'online' => 'Online'.($interview->meeting_link ? ': '.$interview->meeting_link : ' - the link will be shared before the interview.'),
            'phone' => 'By phone - we will call you on '.($applicant->phone ?: 'the number you provided').'.',
            default => 'In person'.($interview->location ? ' at '.$interview->location : ''),
        };

        try {
            Mail::to($applicant->email)->send(new SystemMail(
                subjectLine: ($rescheduled ? 'Interview rescheduled: ' : 'Interview invitation: ').$interview->vacancy->title,
                heading: $rescheduled ? 'Your interview has been rescheduled' : 'You are invited to an interview',
                lines: array_values(array_filter([
                    $rescheduled
                        ? 'Your interview for the '.$interview->vacancy->title.' role has moved to a new time.'
                        : 'Congratulations - you have been shortlisted for the '.$interview->vacancy->title.' role.',
                    'Date and time: '.$interview->scheduled_at->format('l d F Y, H:i').' ('.$interview->duration_minutes.' minutes).',
                    'Where: '.$where,
                    $interview->candidate_message,
                    'Application reference: '.$applicant->reference.'. Please reply to this email to confirm your attendance.',
                ])),
                actionUrl: $interview->mode === 'online' && $interview->meeting_link ? $interview->meeting_link : null,
                actionLabel: 'Join meeting',
                greeting: 'Dear '.strtok($applicant->name, ' '),
            ));
            $interview->forceFill(['candidate_notified_at' => now()])->save();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function emailCandidateCancelled(RecruitmentInterview $interview): void
    {
        try {
            Mail::to($interview->applicant->email)->send(new SystemMail(
                subjectLine: 'Interview cancelled: '.$interview->vacancy->title,
                heading: 'Your interview has been cancelled',
                lines: ['Your interview for the '.$interview->vacancy->title.' role on '.$interview->scheduled_at->format('d F Y, H:i').' has been cancelled. We will contact you if a new date is set.'],
                greeting: 'Dear '.strtok($interview->applicant->name, ' '),
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
