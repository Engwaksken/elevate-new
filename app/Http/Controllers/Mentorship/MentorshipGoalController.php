<?php

namespace App\Http\Controllers\Mentorship;

use App\Http\Controllers\Controller;
use App\Models\MentorMatch;
use App\Models\MentorshipGoal;
use Illuminate\Http\Request;

class MentorshipGoalController extends Controller
{
    public function store(Request $request, MentorMatch $match)
    {
        abort_unless(in_array(auth()->id(),[$match->mentor_user_id,$match->mentee_user_id],true),403);

        $data = $request->validate([
            'title'=>['required','string','max:190'],
            'description'=>['nullable','string'],
            'target_date'=>['nullable','date'],
        ]);

        $match->goals()->create($data);

        return back()->with('success','Mentorship goal added.');
    }

    public function update(Request $request, MentorshipGoal $goal)
    {
        $goal->load('match');

        abort_unless(in_array(auth()->id(),[$goal->match->mentor_user_id,$goal->match->mentee_user_id],true),403);

        $goal->update($request->validate([
            'title'=>['required','string','max:190'],
            'description'=>['nullable','string'],
            'target_date'=>['nullable','date'],
            'progress_percent'=>['required','integer','min:0','max:100'],
            'status'=>['required','in:not_started,in_progress,completed,cancelled'],
        ]));

        return back()->with('success','Goal updated.');
    }
}
