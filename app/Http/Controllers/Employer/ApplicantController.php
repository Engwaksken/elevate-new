<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\JobApplication;
use App\Services\JobApplicationService;
use Illuminate\Http\Request;

class ApplicantController extends Controller
{
    public function index()
    {
        $employerId = Employer::where('owner_user_id',auth()->id())->value('id');

        return view('employer.applicants', [
            'applications'=>JobApplication::with(['job','user'])
                ->whereHas('job',fn($q)=>$q->where('employer_id',$employerId))
                ->latest()
                ->paginate(25),
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
