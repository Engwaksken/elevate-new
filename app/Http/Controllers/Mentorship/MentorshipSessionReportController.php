<?php

namespace App\Http\Controllers\Mentorship;

use App\Http\Controllers\Controller;
use App\Models\MentorshipSession;
use App\Models\MentorshipSessionReport;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;

class MentorshipSessionReportController extends Controller
{
    public function store(Request $request, MentorshipSession $session, NotificationDispatcher $dispatcher)
    {
        $session->loadMissing('match');
        $match = $session->match;
        $userId = (int) auth()->id();

        abort_unless($match && in_array($userId, [(int) $match->mentor_user_id, (int) $match->mentee_user_id], true), 403);

        $role = $userId === (int) $match->mentor_user_id ? 'mentor' : 'mentee';

        $data = $request->validate([
            'summary' => ['required', 'string', 'max:5000'],
            'challenges' => ['nullable', 'string', 'max:5000'],
            'achievements' => ['nullable', 'string', 'max:5000'],
        ]);

        $report = MentorshipSessionReport::updateOrCreate(
            ['mentorship_session_id' => $session->id, 'role' => $role],
            [
                'submitted_by' => $userId,
                'summary' => $data['summary'],
                'challenges' => $data['challenges'] ?? null,
                'achievements' => $data['achievements'] ?? null,
                'mentor_attended' => $request->boolean('mentor_attended'),
                'mentee_attended' => $request->boolean('mentee_attended'),
                'submitted_at' => now(),
            ]
        );

        $recipients = User::query()
            ->where('status', 'active')
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', [
                'placement-officer', 'super-administrator', 'super-admin', 'administrator',
            ]))
            ->get();

        $dispatcher->notifyMany(
            $recipients,
            'mentorship_session_report',
            'Mentorship session report',
            auth()->user()->name.' submitted a '.$role.' report for "'.$session->title.'".',
            route('admin.reports.mentorship-tracking'),
            ['mentorship_session_id' => $session->id, 'mentorship_session_report_id' => $report->id, 'role' => $role]
        );

        return back()->with('success', 'Session report submitted.');
    }
}
