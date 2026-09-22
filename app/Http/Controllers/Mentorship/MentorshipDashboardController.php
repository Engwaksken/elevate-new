<?php

namespace App\Http\Controllers\Mentorship;

use App\Http\Controllers\Controller;
use App\Models\MentorMatch;

class MentorshipDashboardController extends Controller
{
    public function index()
    {
        return view('mentorship.dashboard', [
            'mentorMatches'=>MentorMatch::with('mentee')
                ->where('mentor_user_id',auth()->id())
                ->whereIn('status',['active','pending'])
                ->get(),
            'menteeMatches'=>MentorMatch::with('mentor')
                ->where('mentee_user_id',auth()->id())
                ->whereIn('status',['active','pending'])
                ->get(),
        ]);
    }
}
