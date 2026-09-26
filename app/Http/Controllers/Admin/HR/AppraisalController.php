<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\AppraisalKpiScore;
use App\Models\Employee;
use App\Models\HrKpiTemplate;
use App\Models\User;
use App\Services\AppraisalScoreService;
use App\Services\HR\AppraisalProgressService;
use Illuminate\Http\Request;

class AppraisalController extends Controller
{
    public function index(AppraisalProgressService $progressService)
    {
        $appraisals=Appraisal::with([
                'employee.user',
                'cycle',
                'manager',
                'kpiTemplate',
            ])
            ->latest()
            ->paginate(25);

        foreach($appraisals as $appraisal){
            if($appraisal->hr_kpi_template_id){
                $progressService->refresh($appraisal);
            }
        }

        return view('admin.hr.appraisals.index',[
            'cycles'=>AppraisalCycle::latest()->get(),
            'appraisals'=>$appraisals,
            'employees'=>Employee::with('user')->orderBy('employee_number')->get(),
            'managers'=>User::where('user_type','staff')->orderBy('name')->get(),
            'templates'=>HrKpiTemplate::where('is_active',true)->latest()->get(),
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
            'hr_kpi_template_id'=>['required','exists:hr_kpi_templates,id'],
        ]);

        $employee=Employee::findOrFail($data['employee_id']);

        $appraisal=Appraisal::firstOrCreate([
            'appraisal_cycle_id'=>$data['appraisal_cycle_id'],
            'employee_id'=>$data['employee_id'],
        ],[
            'manager_user_id'=>$data['manager_user_id'] ?? $employee->supervisor_user_id,
            'hr_kpi_template_id'=>$data['hr_kpi_template_id'],
            'status'=>'self_assessment',
        ]);

        $appraisal->update([
            'manager_user_id'=>$data['manager_user_id'] ?? $employee->supervisor_user_id,
            'hr_kpi_template_id'=>$data['hr_kpi_template_id'],
        ]);

        $template=HrKpiTemplate::with('items')->findOrFail($data['hr_kpi_template_id']);

        foreach($template->items as $item){
            if(in_array($item->item_type,['kpi','okr','behavioral'],true)
                && !($item->item_type==='behavioral' && data_get($item->meta,'group'))){
                AppraisalKpiScore::firstOrCreate([
                    'appraisal_id'=>$appraisal->id,
                    'hr_kpi_template_item_id'=>$item->id,
                ]);
            }
        }

        return back()->with('success','Appraisal assigned to staff member.');
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
