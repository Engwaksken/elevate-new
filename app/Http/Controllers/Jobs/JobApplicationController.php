<?php
namespace App\Http\Controllers\Jobs;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;
class JobApplicationController extends Controller
{
    public function store(Request $request,Job $job)
    {
        abort_unless($job->status==='published',404);

        $data=$request->validate(['resume_id'=>['nullable','exists:resumes,id'],'cover_letter'=>['nullable','string','max:10000']]);

        $application=JobApplication::firstOrCreate(
            ['job_id'=>$job->id,'user_id'=>auth()->id()],
            $data+['status'=>'submitted','applied_at'=>now()]
        );

        if ($application->wasRecentlyCreated) {
            $application->statusHistory()->create([
                'status'=>'submitted',
                'changed_by'=>auth()->id(),
                'changed_at'=>now(),
            ]);

            $this->notifyStakeholders($job,$application);
        }

        return redirect()->route('jobs.applications')->with('success','Application submitted.');
    }

    private function notifyStakeholders(Job $job, JobApplication $application): void
    {
        $dispatcher=app(NotificationDispatcher::class);
        $applicant=auth()->user();
        $job->loadMissing('employer.owner');
        $employerOwner=$job->employer?->owner;
        $payload=[
            'job_id'=>$job->id,
            'job_application_id'=>$application->id,
            'applicant_id'=>$applicant->id,
            'applicant_name'=>$applicant->name,
            'job_title'=>$job->title,
        ];

        if ($employerOwner) {
            $dispatcher->notify(
                $employerOwner,
                'job_application_received',
                'New application: '.$job->title,
                $applicant->name.' applied for "'.$job->title.'". Review the application and respond.',
                route('employer.applicants.index'),
                $payload+['notify_applicant_on_open'=>true]
            );
        }

        $officers=User::query()
            ->where('status','active')
            ->whereHas('roles',fn($q)=>$q->where('slug','placement-officer'))
            ->get();

        foreach ($officers as $officer) {
            if ($employerOwner && (int) $officer->id===(int) $employerOwner->id) {
                continue;
            }

            $dispatcher->notify(
                $officer,
                'job_application_received',
                'New job application: '.$job->title,
                $applicant->name.' applied for "'.$job->title.'". Follow up with the employer.',
                route('admin.jobs.applications.index'),
                $payload+['notify_applicant_on_open'=>false]
            );
        }

        $dispatcher->notify(
            $applicant,
            'job_application',
            'Application submitted',
            'Your application for "'.$job->title.'" was submitted. You will be notified as it progresses.',
            route('jobs.applications'),
            $payload,
            false
        );
    }
    public function index(){ $base=JobApplication::query()->where('user_id',auth()->id()); return view('jobs.applications',['applications'=>(clone $base)->with(['job.employer','statusHistory'])->latest()->paginate(20),'stats'=>['total'=>(clone $base)->count(),'review'=>(clone $base)->whereIn('status',['submitted','under_review','reviewing'])->count(),'shortlisted'=>(clone $base)->whereIn('status',['shortlisted','interview','interview_scheduled'])->count(),'successful'=>(clone $base)->whereIn('status',['offered','offer','hired','successful'])->count()]]); }
    public function withdraw(JobApplication $application){abort_unless($application->user_id===auth()->id(),403);abort_if(in_array($application->status,['hired','rejected','withdrawn'],true),422);$application->update(['status'=>'withdrawn']);return back()->with('success','Application withdrawn.');}
}