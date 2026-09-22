<?php
namespace App\Http\Controllers\Admin\ME;

use App\Http\Controllers\Controller;
use App\Models\Indicator;
use App\Models\IndicatorTarget;
use App\Models\IndicatorResult;
use App\Services\IndicatorCalculationService;
use Illuminate\Http\Request;

class IndicatorController extends Controller
{
    public function index()
    {
        return view('admin.indicators.index',[
            'indicators'=>Indicator::withCount(['targets','results'])->latest()->paginate(20)
        ]);
    }

    public function store(Request $request)
    {
        $data=$request->validate([
            'programme_id'=>['nullable','exists:programmes,id'],
            'project_id'=>['nullable','exists:projects,id'],
            'result_id'=>['nullable','exists:results,id'],
            'name'=>['required','string','max:255'],
            'code'=>['nullable','string','max:100','unique:indicators,code'],
            'definition'=>['nullable','string'],
            'result_level'=>['required','in:impact,outcome,output,activity'],
            'indicator_type'=>['required','in:number,percentage,rate,ratio,currency,binary,text'],
            'unit_of_measure'=>['nullable','string','max:100'],
            'baseline_numeric'=>['nullable','numeric'],
            'baseline_text'=>['nullable','string'],
            'frequency'=>['nullable','string','max:100'],
            'data_source'=>['nullable','string'],
            'means_of_verification'=>['nullable','string'],
            'responsible_user_id'=>['nullable','exists:users,id'],
            'status'=>['required','in:draft,active,inactive,closed'],
            'calculation_key'=>['nullable','string','max:100'],
        ]);

        Indicator::create($data);
        return back()->with('success','Indicator created.');
    }

    public function addTarget(Request $request, Indicator $indicator)
    {
        $indicator->targets()->create($request->validate([
            'period_type'=>['required','string','max:50'],
            'period_label'=>['required','string','max:100'],
            'period_start'=>['nullable','date'],
            'period_end'=>['nullable','date','after_or_equal:period_start'],
            'target_numeric'=>['nullable','numeric'],
            'target_text'=>['nullable','string'],
        ]));
        return back()->with('success','Indicator target added.');
    }

    public function calculate(Indicator $indicator, IndicatorCalculationService $service)
    {
        $value=$service->calculate($indicator);
        abort_if($value===null,422,'This indicator has no automatic calculation configured.');

        IndicatorResult::create([
            'indicator_id'=>$indicator->id,
            'reporting_period'=>now()->format('Y-m-d'),
            'actual_numeric'=>$value,
            'data_source'=>'system',
            'verification_status'=>'submitted',
            'entered_by'=>auth()->id(),
        ]);

        return back()->with('success','Indicator calculated from system data.');
    }

    public function verify(IndicatorResult $result)
    {
        $result->update([
            'verification_status'=>'verified',
            'verified_by'=>auth()->id(),
            'verified_at'=>now(),
        ]);
        return back()->with('success','Indicator result verified.');
    }
}
