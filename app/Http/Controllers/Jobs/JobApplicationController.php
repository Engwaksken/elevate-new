<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;

class JobApplicationController extends Controller
{
    public function store(Request $request, Job $job)
    {
        abort_unless($job->status === 'published',404);

        $data = $request->validate([
            'resume_id'=>['nullable','exists:resumes,id'],
            'cover_letter'=>['nullable','string','max:10000'],
        ]);

        JobApplication::firstOrCreate(
            ['job_id'=>$job->id,'user_id'=>auth()->id()],
            $data + ['status'=>'submitted','applied_at'=>now()]
        );

        return redirect()->route('jobs.applications')
            ->with('success','Application submitted.');
    }

    public function index()
    {
        return view('jobs.applications', [
            'applications'=>JobApplication::with(['job.employer'])
                ->where('user_id',auth()->id())
                ->latest()
                ->paginate(20),
        ]);
    }

    public function withdraw(JobApplication $application)
    {
        abort_unless($application->user_id === auth()->id(),403);

        abort_if(in_array($application->status,['hired','rejected','withdrawn'],true),422);

        $application->update(['status'=>'withdrawn']);

        return back()->with('success','Application withdrawn.');
    }
}
