<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Enrolment;
use App\Models\JobApplication;
use App\Models\MentorMatch;
use App\Models\Resume;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $enrolments = Enrolment::query()
            ->with('course')
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->limit(4)
            ->get();

        $activeMentorship = MentorMatch::query()
            ->with([
                'mentor',
                'sessions' => fn ($query) => $query
                    ->where('scheduled_at', '>=', now())
                    ->orderBy('scheduled_at'),
            ])
            ->where('mentee_user_id', $user->id)
            ->whereIn('status', ['pending', 'active', 'paused'])
            ->latest('updated_at')
            ->first();

        $jobApplications = JobApplication::query()
            ->with('job')
            ->where('user_id', $user->id)
            ->latest('applied_at')
            ->limit(4)
            ->get();

        $upcomingEvents = CalendarEvent::query()
            ->where(function ($query) use ($user) {
                $query->whereNull('responsible_user_id')
                    ->orWhere('responsible_user_id', $user->id);
            })
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        $notifications = UserNotification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', [
            'user' => $user->load('profile'),
            'enrolments' => $enrolments,
            'activeMentorship' => $activeMentorship,
            'jobApplications' => $jobApplications,
            'upcomingEvents' => $upcomingEvents,
            'notifications' => $notifications,
            'unreadNotifications' => UserNotification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->count(),
            'resumeCount' => Resume::query()
                ->where('user_id', $user->id)
                ->count(),
        ]);
    }
}
