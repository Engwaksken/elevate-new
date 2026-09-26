<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\AppraisalKpiScore;
use App\Models\AppraisalKpiWeeklyUpdate;
use App\Models\Employee;
use App\Services\HR\AppraisalProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffAppraisalController extends Controller
{
    public function index(Request $request, AppraisalProgressService $progressService)
    {
        $employee=Employee::where('user_id',auth()->id())->first();

        $appraisals=$employee
            ? Appraisal::with(['cycle','kpiTemplate'])
                ->where('employee_id',$employee->id)
                ->latest()
                ->paginate(12)
            : collect();

        if($employee){
            foreach($appraisals as $appraisal){
                $progressService->refresh($appraisal);
            }
        }

        return view('hr.appraisals.index',[
            'employee'=>$employee,
            'appraisals'=>$appraisals,
            'teamCount'=>Appraisal::where('manager_user_id',auth()->id())
                ->whereNotNull('employee_submitted_at')
                ->whereNull('manager_submitted_at')
                ->count(),
        ]);
    }

    public function team(Request $request)
    {
        $query=Appraisal::with(['employee.user','cycle','kpiTemplate'])
            ->where('manager_user_id',auth()->id());

        if($status=$request->get('status')){
            $query->where('status',$status);
        }

        return view('hr.appraisals.team',[
            'appraisals'=>$query->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function show(Appraisal $appraisal, AppraisalProgressService $progressService)
    {
        $this->authoriseViewer($appraisal);

        $appraisal=$progressService->refresh($appraisal);

        $appraisal->load([
            'employee.user',
            'cycle',
            'manager',
            'kpiTemplate.items',
            'kpiScores',
            'kpiWeeklyUpdates',
        ]);

        return view('hr.appraisals.show',[
            'appraisal'=>$appraisal,
            'items'=>$appraisal->kpiTemplate?->items ?? collect(),
            'scores'=>$appraisal->kpiScores->keyBy('hr_kpi_template_item_id'),
            'weekly'=>$appraisal->kpiWeeklyUpdates
                ->groupBy('hr_kpi_template_item_id')
                ->map->keyBy('week_number'),
            'isEmployee'=>$this->isEmployee($appraisal),
            'isManager'=>$this->isManager($appraisal),
            'selfCompletion'=>$progressService->selfCompletion($appraisal),
            'managerCompletion'=>$progressService->managerCompletion($appraisal),
            'performanceLabel'=>$progressService->ratingLabel(
                $appraisal->performance_percent !== null
                    ? (float)$appraisal->performance_percent
                    : null
            ),
        ]);
    }

    public function saveSelf(Request $request, Appraisal $appraisal, AppraisalProgressService $progressService)
    {
        abort_unless($this->isEmployee($appraisal),403);
        abort_if($appraisal->employee_submitted_at,422,'The self assessment has already been submitted.');

        $data=$request->validate([
            'scores'=>['nullable','array'],
            'scores.*.employee_rating'=>['nullable','numeric','min:1','max:5'],
            'scores.*.okr_percent'=>['nullable','numeric','min:0','max:100'],
            'scores.*.employee_comment'=>['nullable','string','max:5000'],
            'scores.*.evidence_note'=>['nullable','string','max:5000'],
            'scores.*.evidence_url'=>['nullable','url','max:1000'],
            'weekly'=>['nullable','array'],
            'weekly.*.*.actual_target'=>['nullable','string','max:500'],
            'weekly.*.*.comment'=>['nullable','string','max:2000'],
            'overall_employee_comments'=>['nullable','string','max:10000'],
        ]);

        DB::transaction(function () use ($data,$request,$appraisal) {
            foreach(($data['scores'] ?? []) as $itemId=>$score){
                $payload=collect($score)
                    ->only([
                        'employee_rating',
                        'okr_percent',
                        'employee_comment',
                        'evidence_note',
                        'evidence_url',
                    ])
                    ->all();

                AppraisalKpiScore::updateOrCreate(
                    [
                        'appraisal_id'=>$appraisal->id,
                        'hr_kpi_template_item_id'=>$itemId,
                    ],
                    $payload
                );
            }

            foreach(($data['weekly'] ?? []) as $itemId=>$weeks){
                foreach($weeks as $week=>$update){
                    $actual=trim((string)($update['actual_target'] ?? ''));
                    $comment=trim((string)($update['comment'] ?? ''));

                    $key=[
                        'appraisal_id'=>$appraisal->id,
                        'hr_kpi_template_item_id'=>$itemId,
                        'week_number'=>(int)$week,
                    ];

                    if($actual==='' && $comment===''){
                        AppraisalKpiWeeklyUpdate::where($key)->delete();
                        continue;
                    }

                    AppraisalKpiWeeklyUpdate::updateOrCreate(
                        $key,
                        [
                            'actual_target'=>$actual !== '' ? $actual : null,
                            'comment'=>$comment !== '' ? $comment : null,
                        ]
                    );
                }
            }

            $updates=[];

            if($request->exists('overall_employee_comments')){
                $updates['employee_comments']=$data['overall_employee_comments'] ?? null;
            }

            if($appraisal->status==='goal_setting'){
                $updates['status']='self_assessment';
            }

            if($updates){
                $appraisal->update($updates);
            }
        });

        $progressService->refresh($appraisal->fresh());

        return back()->with('success','Self assessment draft and comments saved.');
    }

    public function submitSelf(Appraisal $appraisal, AppraisalProgressService $progressService)
    {
        abort_unless($this->isEmployee($appraisal),403);
        abort_if($appraisal->employee_submitted_at,422,'The self assessment has already been submitted.');

        $appraisal=$progressService->refresh($appraisal);

        if($progressService->selfCompletion($appraisal)<100){
            return back()->with('error','Complete all required self-rating items before submitting.');
        }

        $appraisal->update([
            'employee_submitted_at'=>now(),
            'status'=>'manager_review',
        ]);

        $progressService->refresh($appraisal);

        return back()->with('success','Self assessment submitted to your manager.');
    }

    public function saveManager(Request $request, Appraisal $appraisal, AppraisalProgressService $progressService)
    {
        abort_unless($this->isManager($appraisal),403);
        abort_unless($appraisal->employee_submitted_at,422,'The employee must submit the self assessment first.');
        abort_if($appraisal->manager_submitted_at,422,'The manager review has already been submitted.');

        $data=$request->validate([
            'scores'=>['nullable','array'],
            'scores.*.manager_rating'=>['nullable','numeric','min:1','max:5'],
            'scores.*.agreed_rating'=>['nullable','numeric','min:1','max:5'],
            'scores.*.manager_comment'=>['nullable','string','max:5000'],
            'overall_manager_comments'=>['nullable','string','max:10000'],
            'development_plan'=>['nullable','string','max:10000'],
        ]);

        foreach(($data['scores'] ?? []) as $itemId=>$score){
            AppraisalKpiScore::updateOrCreate(
                [
                    'appraisal_id'=>$appraisal->id,
                    'hr_kpi_template_item_id'=>$itemId,
                ],
                $score
            );
        }

        $appraisal->update([
            'manager_comments'=>$data['overall_manager_comments'] ?? $appraisal->manager_comments,
            'development_plan'=>$data['development_plan'] ?? $appraisal->development_plan,
            'status'=>'manager_review',
        ]);

        $progressService->refresh($appraisal);

        return back()->with('success','Manager review draft saved.');
    }

    public function submitManager(Appraisal $appraisal, AppraisalProgressService $progressService)
    {
        abort_unless($this->isManager($appraisal),403);
        abort_unless($appraisal->employee_submitted_at,422,'The employee must submit first.');
        abort_if($appraisal->manager_submitted_at,422,'The manager review has already been submitted.');

        $appraisal=$progressService->refresh($appraisal);

        if($progressService->managerCompletion($appraisal)<100){
            return back()->with('error','Complete all manager and agreed ratings before submitting.');
        }

        $appraisal->update([
            'manager_submitted_at'=>now(),
            'manager_acknowledged_at'=>now(),
            'manager_acknowledgement_name'=>auth()->user()->name,
            'status'=>'hr_review',
        ]);

        $progressService->refresh($appraisal);

        return back()->with('success','Manager review submitted to HR.');
    }

    public function acknowledge(Appraisal $appraisal, AppraisalProgressService $progressService)
    {
        abort_unless($this->isEmployee($appraisal),403);
        abort_unless($appraisal->hr_finalised_at,422,'HR must finalise the appraisal before acknowledgement.');

        if(!$appraisal->employee_acknowledged_at){
            $appraisal->update([
                'employee_acknowledged_at'=>now(),
                'employee_acknowledgement_name'=>auth()->user()->name,
                'status'=>'completed',
            ]);
        }

        $progressService->refresh($appraisal);

        return back()->with('success','Appraisal acknowledged.');
    }

    private function authoriseViewer(Appraisal $appraisal): void
    {
        abort_unless(
            $this->isEmployee($appraisal)
            || $this->isManager($appraisal)
            || (auth()->user()?->user_type==='staff' && auth()->user()?->hasPermission('appraisals.view')),
            403
        );
    }

    private function isEmployee(Appraisal $appraisal): bool
    {
        return (int)$appraisal->employee?->user_id === (int)auth()->id();
    }

    private function isManager(Appraisal $appraisal): bool
    {
        return (int)$appraisal->manager_user_id === (int)auth()->id();
    }
}
