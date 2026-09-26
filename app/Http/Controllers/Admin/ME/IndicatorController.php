<?php

namespace App\Http\Controllers\Admin\ME;

use App\Http\Controllers\Controller;
use App\Models\Indicator;
use App\Models\IndicatorResult;
use App\Models\Programme;
use App\Models\Project;
use App\Models\User;
use App\Services\IndicatorCalculationService;
use Illuminate\Http\Request;

class IndicatorController extends Controller
{
    public function index(Request $request)
    {
        $query = Indicator::with([
                'targets' => fn ($q) => $q->latest(),
                'results' => fn ($q) => $q->latest(),
            ])
            ->withCount(['targets','results'])
            ->latest();

        if ($search = trim((string)$request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name','like',"%{$search}%")
                    ->orWhere('code','like',"%{$search}%")
                    ->orWhere('definition','like',"%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status',$status);
        }

        if ($level = $request->get('result_level')) {
            $query->where('result_level',$level);
        }

        if ($type = $request->get('indicator_type')) {
            $query->where('indicator_type',$type);
        }

        $perPage = in_array((int)$request->get('per_page'),[10,20,25,50,100],true)
            ? (int)$request->get('per_page') : 20;

        return view('admin.indicators.index',[
            'indicators'=>$query->paginate($perPage)->withQueryString(),
            'programmes'=>Programme::orderBy('name')->get(),
            'projects'=>Project::orderBy('name')->get(),
            'users'=>User::where('user_type','staff')->orderBy('name')->get(),
            'stats'=>[
                'total'=>Indicator::count(),
                'active'=>Indicator::where('status','active')->count(),
                'pending'=>IndicatorResult::where('verification_status','submitted')->count(),
                'verified'=>IndicatorResult::where('verification_status','verified')->count(),
            ],
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

        abort_if(
            $value===null,
            422,
            'This indicator has no automatic calculation configured.'
        );

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
