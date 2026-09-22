<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\JobApplication;
use Illuminate\Http\Request;

class InterviewController extends Controller
{
    public function store(Request $request, JobApplication $application)
    {
        $employerId = Employer::where('owner_user_id',auth()->id())->value('id');
        abort_unless($application->job()->where('employer_id',$employerId)->exists(),403);

        $data = $request->validate([
            'scheduled_at'=>['required','date','after:now'],
            'venue'=>['nullable','string','max:190'],
            'meeting_link'=>['nullable','url'],
            'notes'=>['nullable','string'],
        ]);

        $application->interviews()->create($data);
        $application->update(['status'=>'interview']);

        return back()->with('success','Interview scheduled.');
    }
}
