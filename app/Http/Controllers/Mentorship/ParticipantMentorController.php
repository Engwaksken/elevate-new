<?php

namespace App\Http\Controllers\Mentorship;

use App\Http\Controllers\Controller;
use App\Models\MenteeProfile;
use App\Models\MentorMatch;
use App\Models\MentorProfile;
use App\Models\User;
use App\Services\MentorRecommendationService;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ParticipantMentorController extends Controller
{
    public const MAX_ACTIVE_MENTORS = 3;
    public const WINDOW_MONTHS = 3;

    public function index(MentorRecommendationService $recommendations)
    {
        $mentee = $this->menteeProfile();

        $mentors = $recommendations->recommendWithAi($mentee, 12);

        $matches = $this->activeMatches();

        return view('mentorship.mentors', [
            'mentors' => $mentors,
            'matches' => $matches,
            'maxMentors' => self::MAX_ACTIVE_MENTORS,
            'windowMonths' => self::WINDOW_MONTHS,
        ]);
    }

    public function store(Request $request, MentorRecommendationService $recommendations, NotificationDispatcher $dispatcher)
    {
        $data = $request->validate([
            'mentor_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $admission = app(\App\Services\AdmissionService::class);

        // The entry assessment gates participants only; staff (instructors /
        // trainers using mentorship as mentees) are never held back by it.
        if (! auth()->user()->isStaff() && $admission->requiredForMentorship() && $admission->pending(auth()->user())) {
            throw ValidationException::withMessages([
                'mentor_user_id' => 'Pass the entry assessment before selecting a mentor.',
            ]);
        }

        $mentee = $this->menteeProfile();

        if ($this->activeMatches()->count() >= self::MAX_ACTIVE_MENTORS) {
            throw ValidationException::withMessages([
                'mentor_user_id' => 'You can have up to '.self::MAX_ACTIVE_MENTORS.' mentors for a '.self::WINDOW_MONTHS.'-month period. Finish a current mentorship before adding another.',
            ]);
        }

        $mentor = User::findOrFail($data['mentor_user_id']);
        $profile = MentorProfile::where('user_id', $mentor->id)->where('status', 'approved')->first();

        if (! $profile) {
            throw ValidationException::withMessages([
                'mentor_user_id' => 'That mentor is not available for selection.',
            ]);
        }

        $exists = MentorMatch::where('mentor_user_id', $mentor->id)
            ->where('mentee_user_id', auth()->id())
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'mentor_user_id' => 'You are already matched with this mentor.',
            ]);
        }

        $score = optional($recommendations->recommend($mentee, 30)->firstWhere('user_id', $mentor->id))->recommendation_score;

        $match = MentorMatch::create([
            'mentor_user_id' => $mentor->id,
            'mentee_user_id' => auth()->id(),
            'programme_id' => $mentee->programme_id,
            'cohort_id' => $mentee->cohort_id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(self::WINDOW_MONTHS)->toDateString(),
            'status' => 'active',
            'matched_by' => auth()->id(),
            'matching_score' => $score,
        ]);

        $dispatcher->notify(
            $mentor,
            'mentorship_match',
            'New mentee request',
            auth()->user()->name.' selected you as a mentor.',
            route('mentorship.dashboard'),
            ['mentor_match_id' => $match->id, 'mentee_id' => auth()->id()],
            false
        );

        return redirect()->route('mentorship.mentors.index')
            ->with('success', 'Mentor selected. Your mentorship runs for '.self::WINDOW_MONTHS.' months.');
    }

    public function destroy(MentorMatch $match)
    {
        abort_unless((int) $match->mentee_user_id === (int) auth()->id(), 403);

        $match->update(['status' => 'completed']);

        return redirect()->route('mentorship.mentors.index')->with('success', 'Mentorship ended.');
    }

    private function activeMatches()
    {
        return MentorMatch::with('mentor')
            ->where('mentee_user_id', auth()->id())
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString()))
            ->latest('id')
            ->get();
    }

    private function menteeProfile(): MenteeProfile
    {
        return MenteeProfile::firstOrCreate(['user_id' => auth()->id()]);
    }
}
