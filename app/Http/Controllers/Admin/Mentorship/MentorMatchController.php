<?php

namespace App\Http\Controllers\Admin\Mentorship;

use App\Http\Controllers\Controller;
use App\Models\MentorMatch;
use App\Models\MentorProfile;
use App\Models\MenteeProfile;
use Illuminate\Http\Request;

class MentorMatchController extends Controller
{
    public function index()
    {
        return view('admin.mentorship.matches.index', [
            'matches'=>MentorMatch::with(['mentor','mentee'])->latest()->paginate(20),
            'mentors'=>MentorProfile::with('user')->where('status','approved')->get(),
            'mentees'=>MenteeProfile::with('user')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'mentor_user_id'=>['required','exists:users,id'],
            'mentee_user_id'=>['required','different:mentor_user_id','exists:users,id'],
            'programme_id'=>['nullable','exists:programmes,id'],
            'cohort_id'=>['nullable','exists:cohorts,id'],
            'start_date'=>['nullable','date'],
            'end_date'=>['nullable','date','after_or_equal:start_date'],
        ]);

        MentorMatch::create($data + [
            'status'=>'active',
            'matched_by'=>auth()->id(),
        ]);

        return back()->with('success','Mentor matched successfully.');
    }
}
