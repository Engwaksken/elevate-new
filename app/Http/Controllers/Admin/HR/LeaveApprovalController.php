<?php
namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\Request;

class LeaveApprovalController extends Controller
{
    public function index()
    {
        return view('admin.hr.leave.index',[
            'requests'=>LeaveRequest::with(['employee.user','leaveType'])->latest()->paginate(25)
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
        $leave->update([
            'status'=>'rejected',
            'decision_notes'=>$request->input('decision_notes'),
        ]);
        return back()->with('success','Leave rejected.');
    }
}
