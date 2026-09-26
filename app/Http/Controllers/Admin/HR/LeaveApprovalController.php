<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\Request;

class LeaveApprovalController extends Controller
{
    public function index(Request $request)
    {
        $query=LeaveRequest::with(['employee.user','leaveType'])->latest();

        if($search=trim((string)$request->get('search'))){
            $query->whereHas('employee.user',fn($u)=>$u
                ->where('name','like',"%{$search}%")
                ->orWhere('email','like',"%{$search}%"));
        }

        if($status=$request->get('status')) $query->where('status',$status);

        $perPage=in_array((int)$request->get('per_page'),[10,25,50,100],true)
            ? (int)$request->get('per_page') : 25;

        return view('admin.hr.leave.index',[
            'requests'=>$query->paginate($perPage)->withQueryString(),
            'stats'=>[
                'total'=>LeaveRequest::count(),
                'pending'=>LeaveRequest::where('status','pending')->count(),
                'supervisor_approved'=>LeaveRequest::where('status','supervisor_approved')->count(),
                'approved'=>LeaveRequest::where('status','approved')->count(),
            ],
        ]);
    }

    public function supervisorApprove(LeaveRequest $leave)
    {
        $leave->update([
            'status'=>'supervisor_approved',
            'supervisor_approved_by'=>auth()->id(),
            'supervisor_approved_at'=>now(),
        ]);

        return back()->with('success','Supervisor approval recorded.');
    }

    public function hrApprove(LeaveRequest $leave, LeaveService $service)
    {
        abort_unless($leave->status==='supervisor_approved',422);

        $service->approveFinal($leave);

        return back()->with('success','Leave approved by HR.');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $request->validate(['decision_notes'=>['nullable','string','max:2000']]);

        $leave->update([
            'status'=>'rejected',
            'decision_notes'=>$request->input('decision_notes'),
        ]);

        return back()->with('success','Leave rejected.');
    }
}
