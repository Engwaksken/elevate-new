<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\JobApplication;
use App\Services\JobApplicationService;
use Illuminate\Http\Request;

class ApplicantController extends Controller
{
    use ExportsTables;

    public function index(Request $request)
    {
        $employerId = Employer::where('owner_user_id',auth()->id())->value('id');

        $query = JobApplication::with(['job','user'])
            ->whereHas('job',fn($q)=>$q->where('employer_id',$employerId))
            ->latest();

        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'Applicants',$query,[
                'Applicant'=>'user.name',
                'Email'=>'user.email',
                'Job'=>'job.title',
                'Status'=>fn($a)=>ucwords(str_replace('_',' ',(string)$a->status)),
                'Applied'=>fn($a)=>$a->applied_at ?? $a->created_at,
            ]);
        }

        return view('employer.applicants', [
            'applications'=>$query->paginate(25),
        ]);
    }

    public function status(Request $request, JobApplication $application, JobApplicationService $service)
    {
        $employerId = Employer::where('owner_user_id',auth()->id())->value('id');
        abort_unless($application->job()->where('employer_id',$employerId)->exists(),403);

        $data = $request->validate([
            'status'=>['required','in:under_review,shortlisted,interview,offer,hired,rejected'],
            'notes'=>['nullable','string'],
        ]);

        $service->changeStatus($application,$data['status'],$data['notes'] ?? null);

        return back()->with('success','Application status updated.');
    }
}
