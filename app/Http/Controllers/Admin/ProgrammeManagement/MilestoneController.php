<?php
namespace App\Http\Controllers\Admin\ProgrammeManagement;

use App\Http\Controllers\Controller;
use App\Models\Workplan;
use App\Models\Milestone;
use App\Services\WorkplanProgressService;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function store(Request $request, Workplan $workplan)
    {
        $data=$request->validate([
            'title'=>['required','string','max:190'],
            'description'=>['nullable','string'],
            'start_date'=>['nullable','date'],
            'due_date'=>['nullable','date','after_or_equal:start_date'],
            'responsible_user_id'=>['nullable','exists:users,id'],
            'expected_result'=>['nullable','string'],
            'weight'=>['nullable','numeric','min:0.01'],
            'priority'=>['nullable','string','max:50'],
        ]);
        $workplan->milestones()->create($data);
        return back()->with('success','Milestone added.');
    }

    public function update(Request $request, Milestone $milestone, WorkplanProgressService $service)
    {
        $milestone->update($request->validate([
            'progress_percent'=>['required','numeric','min:0','max:100'],
            'status'=>['required','in:not_started,in_progress,at_risk,delayed,completed,cancelled'],
            'remarks'=>['nullable','string'],
        ]));
        $service->recalculate($milestone->workplan);
        return back()->with('success','Milestone updated.');
    }
}
