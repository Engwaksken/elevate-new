<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Services\HR\AppraisalProgressService;

class AppraisalWorkflowController extends Controller
{
    public function finalise(Appraisal $appraisal, AppraisalProgressService $progressService)
    {
        abort_unless($appraisal->employee_submitted_at,422,'The employee has not submitted the self assessment.');
        abort_unless($appraisal->manager_submitted_at,422,'The manager has not submitted the review.');

        $appraisal=$progressService->refresh($appraisal);

        abort_if(
            $appraisal->performance_percent===null,
            422,
            'The appraisal does not yet have a performance score.'
        );

        $appraisal->update([
            'hr_finalised_at'=>now(),
            'hr_finalised_by'=>auth()->id(),
            'status'=>'awaiting_acknowledgement',
        ]);

        $progressService->refresh($appraisal);

        return back()->with('success','Appraisal finalised and sent to the employee for acknowledgement.');
    }
}
