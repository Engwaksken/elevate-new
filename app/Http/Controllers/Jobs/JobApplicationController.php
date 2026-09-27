<?php
namespace App\Http\Controllers\Jobs;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;
class JobApplicationController extends Controller
{
    public function store(Request $request,Job $job){abort_unless($job->status==='published',404);$data=$request->validate(['resume_id'=>['nullable','exists:resumes,id'],'cover_letter'=>['nullable','string','max:10000']]);JobApplication::firstOrCreate(['job_id'=>$job->id,'user_id'=>auth()->id()],$data+['status'=>'submitted','applied_at'=>now()]);return redirect()->route('jobs.applications')->with('success','Application submitted.');}
    public function index(){ $base=JobApplication::query()->where('user_id',auth()->id()); return view('jobs.applications',['applications'=>(clone $base)->with(['job.employer'])->latest()->paginate(20),'stats'=>['total'=>(clone $base)->count(),'review'=>(clone $base)->whereIn('status',['submitted','under_review','reviewing'])->count(),'shortlisted'=>(clone $base)->whereIn('status',['shortlisted','interview','interview_scheduled'])->count(),'successful'=>(clone $base)->whereIn('status',['offered','offer','hired','successful'])->count()]]); }
    public function withdraw(JobApplication $application){abort_unless($application->user_id===auth()->id(),403);abort_if(in_array($application->status,['hired','rejected','withdrawn'],true),422);$application->update(['status'=>'withdrawn']);return back()->with('success','Application withdrawn.');}
}