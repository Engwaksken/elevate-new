<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Services\HR\AppraisalProgressService;
use App\Services\HR\AppraisalWorkflowService;
use Illuminate\Http\Request;

class AppraisalWorkflowController extends Controller
{
    public function finalise(Appraisal $appraisal, AppraisalProgressService $progressService)
    {
        abort_if(
            in_array($appraisal->status,AppraisalWorkflowService::WORKSPACE_STATUSES,true),
            422,
            'This appraisal follows the KRA/KPI workflow and completes through employee and supervisor confirmation.'
        );

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

    public function lock(Request $request, Appraisal $appraisal, AppraisalWorkflowService $workflow)
    {
        abort_if($appraisal->locked_at,422,'The appraisal is already locked.');

        $data=$request->validate(['reason'=>['nullable','string','max:2000']]);

        $appraisal->update(['locked_at'=>now()]);
        $workflow->note($appraisal,'Locked by HR'.(filled($data['reason'] ?? null) ? ': '.$data['reason'] : '.'));

        return back()->with('success','Appraisal locked. No further edits are possible until HR reopens it.');
    }

    public function reopen(Request $request, Appraisal $appraisal, AppraisalWorkflowService $workflow)
    {
        abort_unless(
            $appraisal->locked_at || $appraisal->status==='completed',
            422,
            'Only locked or completed appraisals can be reopened.'
        );

        $data=$request->validate(['reason'=>['required','string','max:2000']]);

        $appraisal->update([
            'locked_at'=>null,
            'reopened_at'=>now(),
            'reopened_reason'=>$data['reason'],
            'employee_submitted_at'=>null,
            'manager_submitted_at'=>null,
            'meeting_completed_at'=>null,
            'employee_confirmed_at'=>null,
            'supervisor_confirmed_at'=>null,
        ]);

        $workflow->transition($appraisal,'returned_for_revision','Reopened by HR: '.$data['reason']);

        return back()->with('success','Appraisal reopened and returned to the employee for revision.');
    }
}
