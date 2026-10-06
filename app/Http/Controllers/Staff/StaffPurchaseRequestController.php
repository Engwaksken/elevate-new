<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\FundingSource;
use App\Models\Programme;
use App\Models\Project;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestService;
use Illuminate\Http\Request;

/**
 * Staff self-service purchase requests: every staff member can raise a
 * request and follow its approval, but only ever sees their own.
 * Approvals stay on the procurement admin page.
 */
class StaffPurchaseRequestController extends Controller
{
    use ExportsTables;

    public const STATUSES = ['draft','submitted','manager_approved','finance_approved','procurement_review','approved','rejected','ordered','received'];

    public function index(Request $request)
    {
        $mine=PurchaseRequest::where('requester_user_id',auth()->id());
        $query=(clone $mine)->with(['items','approvals.user:id,name'])->latest();

        if($search=trim((string)$request->get('search'))){
            $query->where(function($q) use($search){
                $q->where('request_number','like',"%{$search}%")
                  ->orWhere('justification','like',"%{$search}%")
                  ->orWhereHas('items',fn($i)=>$i->where('item_name','like',"%{$search}%"));
            });
        }

        if(in_array($status=$request->get('status'),self::STATUSES,true)) $query->where('status',$status);

        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'My Purchase Requests',$query,[
                'Request No.'=>'request_number',
                'Items'=>fn($r)=>$r->items->pluck('item_name')->implode(', '),
                'Required Date'=>'required_date',
                'Estimated Total'=>fn($r)=>number_format((float)$r->estimated_total,2),
                'Currency'=>'currency',
                'Status'=>fn($r)=>ucfirst(str_replace('_',' ',(string)$r->status)),
                'Justification'=>'justification',
                'Created'=>'created_at',
            ]);
        }

        $employee=Employee::where('user_id',auth()->id())->first();

        return view('staff.purchase-requests.index',[
            'requests'=>$query->paginate(15)->withQueryString(),
            'programmes'=>Programme::orderBy('name')->get(['id','name']),
            'projects'=>Project::orderBy('name')->get(['id','name']),
            // Pre-select the requester's own department while it is still active.
            'defaultDepartment'=>$employee?->department_id ? Department::active()->whereKey($employee->department_id)->value('name') : null,
            'departmentOptions'=>Department::activeNames(),
            'fundingSourceOptions'=>FundingSource::activeNames(),
            'stats'=>[
                'draft'=>(clone $mine)->where('status','draft')->count(),
                'pending'=>(clone $mine)->whereIn('status',['submitted','manager_approved','finance_approved','procurement_review'])->count(),
                'approved'=>(clone $mine)->whereIn('status',['approved','ordered','received'])->count(),
                'rejected'=>(clone $mine)->where('status','rejected')->count(),
            ],
        ]);
    }

    public function store(Request $request, PurchaseRequestService $requests)
    {
        $data=$request->validate(PurchaseRequestService::rules()+[
            'submit_now'=>['nullable','boolean'],
        ]);

        $purchaseRequest=$requests->create($data,auth()->id(),$request->boolean('submit_now') ? 'submitted' : 'draft');

        return redirect()->route('staff.purchase-requests.index')->with('success',
            $purchaseRequest->status==='submitted'
                ? "Purchase request {$purchaseRequest->request_number} submitted for approval."
                : "Purchase request {$purchaseRequest->request_number} saved as a draft.");
    }

    public function submit(PurchaseRequest $purchaseRequest)
    {
        $this->ensureOwnDraft($purchaseRequest);
        $purchaseRequest->update(['status'=>'submitted']);

        return back()->with('success',"Purchase request {$purchaseRequest->request_number} submitted for approval.");
    }

    public function destroy(PurchaseRequest $purchaseRequest)
    {
        $this->ensureOwnDraft($purchaseRequest);
        $purchaseRequest->items()->delete();
        $purchaseRequest->delete();

        return back()->with('success','Draft purchase request deleted.');
    }

    private function ensureOwnDraft(PurchaseRequest $purchaseRequest): void
    {
        abort_unless((int)$purchaseRequest->requester_user_id===(int)auth()->id(),403);
        abort_unless($purchaseRequest->status==='draft',422,'Only draft requests can be changed.');
    }
}
