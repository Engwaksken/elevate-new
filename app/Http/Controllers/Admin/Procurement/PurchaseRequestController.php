<?php

namespace App\Http\Controllers\Admin\Procurement;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Department;
use App\Models\FundingSource;
use App\Models\Programme;
use App\Models\Project;
use App\Models\PurchaseRequest;
use App\Models\Workplan;
use App\Services\PurchaseRequestService;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    use ExportsTables;
    public function index(Request $request)
    {
        $query=PurchaseRequest::with(['items','approvals'])->latest();

        if($search=trim((string)$request->get('search'))){
            $query->where(function($q) use($search){
                $q->where('request_number','like',"%{$search}%")
                  ->orWhere('department','like',"%{$search}%")
                  ->orWhere('funding_source','like',"%{$search}%")
                  ->orWhere('justification','like',"%{$search}%");
            });
        }

        if($status=$request->get('status')) $query->where('status',$status);

        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'Purchase Requests',$query->with('requester'),[
                'Request No.'=>'request_number',
                'Requester'=>'requester.name',
                'Department'=>'department',
                'Required Date'=>'required_date',
                'Funding Source'=>'funding_source',
                'Items'=>fn($r)=>$r->items->count(),
                'Estimated Total'=>fn($r)=>number_format((float)$r->estimated_total,2),
                'Currency'=>'currency',
                'Status'=>fn($r)=>str_replace('_',' ',(string)$r->status),
                'Justification'=>'justification',
                'Created'=>'created_at',
            ]);
        }

        $perPage=in_array((int)$request->get('per_page'),[10,20,25,50,100],true)
            ? (int)$request->get('per_page') : 20;

        return view('admin.procurement.requests.index',[
            'requests'=>$query->paginate($perPage)->withQueryString(),
            'programmes'=>Programme::orderBy('name')->get(),
            'projects'=>Project::orderBy('name')->get(),
            'workplans'=>Workplan::latest()->get(),
            'activities'=>Activity::latest()->get(),
            'departmentOptions'=>Department::activeNames(),
            'fundingSourceOptions'=>FundingSource::activeNames(),
            'stats'=>[
                'total'=>PurchaseRequest::count(),
                'draft'=>PurchaseRequest::where('status','draft')->count(),
                'submitted'=>PurchaseRequest::where('status','submitted')->count(),
                'approved'=>PurchaseRequest::where('status','approved')->count(),
            ],
        ]);
    }

    public function store(Request $request, PurchaseRequestService $requests)
    {
        $requests->create($request->validate(PurchaseRequestService::rules()), auth()->id());

        return back()->with('success','Purchase request created.');
    }

    public function submit(PurchaseRequest $purchaseRequest)
    {
        abort_unless($purchaseRequest->status==='draft',422,'Only draft requests can be submitted.');
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
