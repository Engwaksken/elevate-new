<?php

namespace App\Http\Controllers\Admin\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Job;

class JobAdminController extends Controller
{
    public function index()
    {
        return view('admin.jobs.index', [
            'jobs'=>Job::with('employer')->latest()->paginate(25),
        ]);
    }

    public function publish(Job $job)
    {
        $job->update([
            'status'=>'published',
            'published_at'=>now(),
        ]);

        return back()->with('success','Job published.');
    }

    public function reject(Job $job)
    {
        $job->update(['status'=>'rejected']);
        return back()->with('success','Job rejected.');
    }
}
