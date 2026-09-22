<?php
namespace App\Services;

use App\Models\JobApplication;
use App\Models\ParticipantOutcome;

class JobApplicationService
{
    public function changeStatus(JobApplication $application, string $status, ?string $notes = null): JobApplication
    {
        $application->update(['status'=>$status]);

        $application->statusHistory()->create([
            'status'=>$status,
            'notes'=>$notes,
            'changed_by'=>auth()->id(),
            'changed_at'=>now(),
        ]);

        if ($status === 'hired') {
            ParticipantOutcome::firstOrCreate(
                [
                    'user_id'=>$application->user_id,
                    'outcome_type'=>'new_wage_employment',
                    'organisation_name'=>$application->job->employer->company_name,
                    'job_title'=>$application->job->title,
                ],
                [
                    'outcome_date'=>now()->toDateString(),
                    'verification_status'=>'submitted',
                ]
            );
        }

        return $application->fresh();
    }
}
