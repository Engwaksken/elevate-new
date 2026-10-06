<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Concerns\ExportsTables;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Support\Facades\DB;

class SavedJobController extends Controller
{
    use ExportsTables;

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

    public function index(Request $request)
    {
        $query = Job::whereIn('id', DB::table('saved_jobs')
            ->where('user_id',auth()->id())
            ->pluck('job_id'))
            ->with('employer');

        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'Saved Jobs',$query,[
                'Title'=>'title',
                'Employer'=>'employer.company_name',
                'Location'=>fn($j)=>collect([$j->location,$j->country])->filter()->implode(', '),
                'Employment Type'=>fn($j)=>ucfirst(str_replace('_',' ',(string)$j->employment_type)),
                'Deadline'=>'application_deadline',
                'Status'=>fn($j)=>ucfirst((string)$j->status),
            ]);
        }

        $jobs = $query->paginate(20);

        return view('jobs.saved',compact('jobs'));
    }
}
