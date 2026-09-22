<?php

namespace App\Http\Controllers\Mentorship;

use App\Http\Controllers\Controller;
use App\Models\MentorMatch;
use App\Models\MentorshipSession;
use Illuminate\Http\Request;

class MentorshipSessionController extends Controller
{
    public function store(Request $request, MentorMatch $match)
    {
        abort_unless(
            in_array(auth()->id(),[$match->mentor_user_id,$match->mentee_user_id],true),
            403
        );

        $data = $request->validate([
            'title'=>['required','string','max:190'],
            'agenda'=>['nullable','string'],
            'scheduled_at'=>['required','date','after:now'],
            'duration_minutes'=>['nullable','integer','min:15','max:480'],
            'meeting_link'=>['nullable','url'],
            'venue'=>['nullable','string','max:190'],
        ]);

        $match->sessions()->create($data);

        return back()->with('success','Mentorship session scheduled.');
    }

    public function complete(Request $request, MentorshipSession $session)
    {
        $session->load('match');

        abort_unless(
            in_array(auth()->id(),[$session->match->mentor_user_id,$session->match->mentee_user_id],true),
            403
        );

        $data = $request->validate([
            'mentor_attended'=>['nullable','boolean'],
            'mentee_attended'=>['nullable','boolean'],
            'session_notes'=>['nullable','string'],
            'agreed_actions'=>['nullable','string'],
            'next_session_at'=>['nullable','date','after:now'],
        ]);

        $session->update($data + ['status'=>'completed']);

        return back()->with('success','Mentorship session completed.');
    }
}
