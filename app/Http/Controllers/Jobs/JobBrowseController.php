<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;

class JobBrowseController extends Controller
{
    public function index(Request $request)
    {
        $query = Job::with('employer')
            ->where('status','published')
            ->where(function($q){
                $q->whereNull('application_deadline')
                  ->orWhereDate('application_deadline','>=',today());
            });

        if ($search = trim((string)$request->get('search'))) {
            $query->where(fn($q)=>$q
                ->where('title','like',"%{$search}%")
                ->orWhere('description','like',"%{$search}%")
                ->orWhere('skills','like',"%{$search}%"));
        }

        if ($type = $request->get('employment_type')) {
            $query->where('employment_type',$type);
        }

        if ($arrangement = $request->get('work_arrangement')) {
            $query->where('work_arrangement',$arrangement);
        }

        return view('jobs.index', [
            'jobs'=>$query->latest('published_at')->paginate(15)->withQueryString(),
        ]);
    }

    public function show(Job $job)
    {
        abort_unless($job->status === 'published',404);
        $job->load('employer');
        return view('jobs.show',compact('job'));
    }
}
