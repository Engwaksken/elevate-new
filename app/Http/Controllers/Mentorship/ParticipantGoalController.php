<?php

namespace App\Http\Controllers\Mentorship;

use App\Http\Controllers\Controller;
use App\Models\MentorMatch;
use App\Models\ParticipantGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lets a mentor (or the mentorship coordinator) review a mentee's personal
 * goals and leave a comment. Goals stay owned by the participant.
 */
class ParticipantGoalController extends Controller
{
    public function review(Request $request, ParticipantGoal $goal): RedirectResponse
    {
        abort_unless($this->canReview($request, $goal), 403);

        $data = $request->validate([
            'mentor_comment' => ['required', 'string', 'max:2000'],
        ]);

        $goal->update([
            'mentor_comment' => $data['mentor_comment'],
            'mentor_reviewed_at' => now(),
            'mentor_reviewed_by' => $request->user()->id,
        ]);

        // Let the participant know her mentor responded.
        try {
            app(\App\Services\NotificationDispatcher::class)->notify(
                $goal->user,
                'goal_review',
                'Your mentor reviewed a goal',
                'Your mentor left feedback on the goal “'.$goal->title.'”.',
                '/profile',
                ['goal_id' => $goal->id]
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', 'Feedback saved for '.$goal->user->name.'.');
    }

    private function canReview(Request $request, ParticipantGoal $goal): bool
    {
        $user = $request->user();

        $isMentor = MentorMatch::query()
            ->where('mentee_user_id', $goal->user_id)
            ->whereIn('status', ['active', 'pending'])
            ->where('mentor_user_id', $user->id)
            ->exists();

        return $isMentor || $user->hasPermission('mentorship.match');
    }
}
