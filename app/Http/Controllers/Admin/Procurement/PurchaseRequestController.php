<?php
namespace App\Http\Controllers\Admin\Procurement;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        return view('admin.procurement.requests.index',[
            'requests'=>PurchaseRequest::with('items')->latest()->paginate(20)
        ]);
    }

    public function store(Request $request, PurchaseRequestNumberService $numbers)
    {
        $data=$request->validate([
            'procurement_plan_id'=>['nullable','exists:procurement_plans,id'],
            'programme_id'=>['nullable','exists:programmes,id'],
            'project_id'=>['nullable','exists:projects,id'],
            'workplan_id'=>['nullable','exists:workplans,id'],
            'activity_id'=>['nullable','exists:activities,id'],
            'department'=>['nullable','string','max:190'],
            'required_date'=>['nullable','date'],
            'funding_source'=>['nullable','string','max:190'],
            'justification'=>['nullable','string'],
            'currency'=>['nullable','string','size:3'],
            'items'=>['required','array','min:1'],
            'items.*.item_name'=>['required','string','max:190'],
            'items.*.specification'=>['nullable','string'],
            'items.*.quantity'=>['required','numeric','min:0.01'],
            'items.*.unit'=>['nullable','string','max:50'],
            'items.*.estimated_unit_cost'=>['nullable','numeric','min:0'],
            'items.*.is_asset'=>['nullable','boolean'],
        ]);

        DB::transaction(function() use($data,$numbers){
            $request=PurchaseRequest::create([
                'request_number'=>$numbers->next(),
                'procurement_plan_id'=>$data['procurement_plan_id'] ?? null,
                'programme_id'=>$data['programme_id'] ?? null,
                'project_id'=>$data['project_id'] ?? null,
                'workplan_id'=>$data['workplan_id'] ?? null,
                'activity_id'=>$data['activity_id'] ?? null,
                'requester_user_id'=>auth()->id(),
                'department'=>$data['department'] ?? null,
                'required_date'=>$data['required_date'] ?? null,
                'funding_source'=>$data['funding_source'] ?? null,
                'justification'=>$data['justification'] ?? null,
                'currency'=>$data['currency'] ?? 'UGX',
                'status'=>'draft',
            ]);

            $total=0;
            foreach($data['items'] as $item){
                $line=(float)$item['quantity']*(float)($item['estimated_unit_cost'] ?? 0);
                $request->items()->create([
                    ...$item,
                    'estimated_total'=>$line,
                    'is_asset'=>(bool)($item['is_asset'] ?? false),
                ]);
                $total+=$line;
            }
            $request->update(['estimated_total'=>$total]);
        });

        return back()->with('success','Purchase request created.');
    }

    public function submit(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->update(['status'=>'submitted']);
        return back()->with('success','Purchase request submitted.');
    }

    public function approve(Request $request, PurchaseRequest $purchaseRequest)
    {
        $stage=$request->validate([
            'approval_stage'=>['required','in:manager,finance,procurement,final'],
            'decision'=>['required','in:approved,rejected,returned'],
            'comments'=>['nullable','string'],
        ]);

        $purchaseRequest->approvals()->create([
            'user_id'=>auth()->id(),
            'approval_stage'=>$stage['approval_stage'],
            'decision'=>$stage['decision'],
            'comments'=>$stage['comments'] ?? null,
            'acted_at'=>now(),
        ]);

        if($stage['decision']==='rejected'){
            $purchaseRequest->update(['status'=>'rejected']);
        } elseif($stage['decision']==='returned'){
            $purchaseRequest->update(['status'=>'draft']);
        } else {
            $map=[
                'manager'=>'manager_approved',
                'finance'=>'finance_approved',
                'procurement'=>'procurement_review',
                'final'=>'approved',
            ];
            $purchaseRequest->update(['status'=>$map[$stage['approval_stage']]]);
        }

        return back()->with('success','Approval action recorded.');
    }
}
