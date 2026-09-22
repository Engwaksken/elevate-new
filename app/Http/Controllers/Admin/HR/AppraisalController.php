<?php
namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\Employee;
use App\Services\AppraisalScoreService;
use Illuminate\Http\Request;

class AppraisalController extends Controller
{
    public function index()
    {
        return view('admin.hr.appraisals.index',[
            'cycles'=>AppraisalCycle::latest()->get(),
            'appraisals'=>Appraisal::with('employee.user')->latest()->paginate(25),
        ]);
    }

    public function createCycle(Request $request)
    {
        AppraisalCycle::create($request->validate([
            'name'=>['required','string','max:190'],
            'cycle_type'=>['required','in:probation,mid_year,annual,special'],
            'start_date'=>['required','date'],
            'end_date'=>['required','date','after_or_equal:start_date'],
            'self_assessment_due'=>['nullable','date'],
            'manager_review_due'=>['nullable','date'],
        ]));
        return back()->with('success','Appraisal cycle created.');
    }

    public function assign(Request $request)
    {
        $data=$request->validate([
            'appraisal_cycle_id'=>['required','exists:appraisal_cycles,id'],
            'employee_id'=>['required','exists:employees,id'],
            'manager_user_id'=>['nullable','exists:users,id'],
        ]);

        Appraisal::firstOrCreate([
            'appraisal_cycle_id'=>$data['appraisal_cycle_id'],
            'employee_id'=>$data['employee_id'],
        ],[
            'manager_user_id'=>$data['manager_user_id'] ?? null,
            'status'=>'goal_setting',
        ]);

        return back()->with('success','Appraisal assigned.');
    }

    public function addObjective(Request $request, Appraisal $appraisal)
    {
        $appraisal->objectives()->create($request->validate([
            'workplan_id'=>['nullable','exists:workplans,id'],
            'milestone_id'=>['nullable','exists:milestones,id'],
            'activity_id'=>['nullable','exists:activities,id'],
            'title'=>['required','string','max:190'],
            'expected_result'=>['nullable','string'],
            'weight'=>['required','numeric','min:0.01'],
        ]));
        return back()->with('success','Objective added.');
    }

    public function recalculate(Appraisal $appraisal, AppraisalScoreService $service)
    {
        $service->recalculate($appraisal);
        return back()->with('success','Appraisal score recalculated.');
    }
}
