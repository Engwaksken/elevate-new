<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Support\Facades\DB;

class SavedJobController extends Controller
{
    public function store(Job $job)
    {
        DB::table('saved_jobs')->updateOrInsert([
            'user_id'=>auth()->id(),
            'job_id'=>$job->id,
        ],[
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        return back()->with('success','Job saved.');
    }

    public function destroy(Job $job)
    {
        DB::table('saved_jobs')
            ->where('user_id',auth()->id())
            ->where('job_id',$job->id)
            ->delete();

        return back()->with('success','Saved job removed.');
    }

    public function index()
    {
        $jobs = Job::whereIn('id', DB::table('saved_jobs')
            ->where('user_id',auth()->id())
            ->pluck('job_id'))
            ->with('employer')
            ->paginate(20);

        return view('jobs.saved',compact('jobs'));
    }
}
