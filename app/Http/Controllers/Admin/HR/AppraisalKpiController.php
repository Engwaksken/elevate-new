<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\AppraisalKpiScore;
use App\Models\HrKpiTemplate;
use App\Models\AppraisalKpiWeeklyUpdate;
use Illuminate\Http\Request;

class AppraisalKpiController extends Controller
{
    public function show(Appraisal $appraisal)
    {
        $appraisal->load(['employee.user','kpiTemplate.items','kpiScores','kpiWeeklyUpdates']);

        return view('admin.hr.appraisals.kpi-score',[
            'appraisal'=>$appraisal,
            'templates'=>HrKpiTemplate::where('is_active',true)->latest()->get(),
            'items'=>$appraisal->kpiTemplate?->items ?? collect(),
            'scores'=>$appraisal->kpiScores->keyBy('hr_kpi_template_item_id'),
            'weekly'=>$appraisal->kpiWeeklyUpdates->groupBy('hr_kpi_template_item_id')->map->keyBy('week_number'),
        ]);
    }

    public function assignTemplate(Request $request, Appraisal $appraisal)
    {
        $data=$request->validate([
            'hr_kpi_template_id'=>['required','exists:hr_kpi_templates,id'],
        ]);

        $appraisal->update(['hr_kpi_template_id'=>$data['hr_kpi_template_id']]);

        $template=HrKpiTemplate::with('items')->findOrFail($data['hr_kpi_template_id']);
        foreach($template->items as $item){
            if(in_array($item->item_type,['kpi','okr','behavioral'],true)){
                AppraisalKpiScore::firstOrCreate([
                    'appraisal_id'=>$appraisal->id,
                    'hr_kpi_template_item_id'=>$item->id,
                ]);
            }
        }

        return back()->with('success','KPI/Appraisal template assigned.');
    }

    public function score(Request $request, Appraisal $appraisal)
    {
        $data=$request->validate([
            'scores'=>['required','array'],
            'scores.*.employee_rating'=>['nullable','numeric','min:0','max:5'],
            'scores.*.manager_rating'=>['nullable','numeric','min:0','max:5'],
            'scores.*.agreed_rating'=>['nullable','numeric','min:0','max:5'],
            'scores.*.okr_percent'=>['nullable','numeric','min:0','max:100'],
            'scores.*.employee_comment'=>['nullable','string','max:5000'],
            'weekly'=>['nullable','array'],
            'weekly.*.*.actual_target'=>['nullable','string','max:500'],
            'weekly.*.*.comment'=>['nullable','string','max:2000'],
            'overall_employee_comments'=>['nullable','string','max:10000'],
            'overall_manager_comments'=>['nullable','string','max:10000'],
            'scores.*.manager_comment'=>['nullable','string','max:5000'],
        ]);

        foreach($data['scores'] as $itemId=>$score){
            AppraisalKpiScore::updateOrCreate(
                ['appraisal_id'=>$appraisal->id,'hr_kpi_template_item_id'=>$itemId],
                $score
            );
        }

        foreach(($data['weekly'] ?? []) as $itemId=>$weeks){
            foreach($weeks as $week=>$update){
                if(trim((string)($update['actual_target'] ?? ''))==='' && trim((string)($update['comment'] ?? ''))===''){
                    continue;
                }

                AppraisalKpiWeeklyUpdate::updateOrCreate(
                    [
                        'appraisal_id'=>$appraisal->id,
                        'hr_kpi_template_item_id'=>$itemId,
                        'week_number'=>(int)$week,
                    ],
                    [
                        'actual_target'=>$update['actual_target'] ?? null,
                        'comment'=>$update['comment'] ?? null,
                    ]
                );
            }
        }

        $template=$appraisal->kpiTemplate()->with('items')->first();
        $allScores=AppraisalKpiScore::where('appraisal_id',$appraisal->id)
            ->get()->keyBy('hr_kpi_template_item_id');

        $weighted=0;
        $weightUsed=0;

        foreach(($template?->items ?? collect())->where('item_type','kra') as $kra){
            $kpiItems=$template->items
                ->where('item_type','kpi')
                ->where('section',$kra->title);

            $ratings=$kpiItems
                ->map(fn($item)=>$allScores->get($item->id)?->agreed_rating)
                ->filter(fn($rating)=>$rating!==null);

            if($ratings->isEmpty() || !$kra->weight) continue;

            $weighted += $ratings->avg() * ((float)$kra->weight / 100);
            $weightUsed += (float)$kra->weight;
        }

        if($weightUsed>0){
            $final=round($weighted / ($weightUsed / 100),2);
        }else{
            $ratings=$allScores->pluck('agreed_rating')->filter(fn($rating)=>$rating!==null);
            $final=$ratings->isNotEmpty() ? round($ratings->avg(),2) : null;
        }

        $appraisalUpdate=['final_score'=>$final];

        if($request->exists('overall_employee_comments')){
            $appraisalUpdate['employee_comments']=$request->input('overall_employee_comments');
        }

        if($request->exists('overall_manager_comments')){
            $appraisalUpdate['manager_comments']=$request->input('overall_manager_comments');
        }

        $appraisal->update($appraisalUpdate);

        return back()->with('success','Appraisal KPI ratings and weighted summary saved.');
    }
}
