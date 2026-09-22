<?php
namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function index()
    {
        $employee=Employee::where('user_id',auth()->id())->firstOrFail();

        return view('hr.leave',[
            'employee'=>$employee,
            'leaveTypes'=>LeaveType::where('is_active',true)->orderBy('name')->get(),
            'requests'=>$employee->leaveRequests()->with('leaveType')->latest()->paginate(20),
        ]);
    }

    public function store(Request $request, LeaveService $service)
    {
        $employee=Employee::where('user_id',auth()->id())->firstOrFail();

        $data=$request->validate([
            'leave_type_id'=>['required','exists:leave_types,id'],
            'start_date'=>['required','date','after_or_equal:today'],
            'end_date'=>['required','date','after_or_equal:start_date'],
            'reason'=>['nullable','string'],
            'handover_user_id'=>['nullable','exists:users,id'],
        ]);

        $data['days_requested']=$service->workingDays($data['start_date'],$data['end_date']);

        $employee->leaveRequests()->create($data+['status'=>'submitted']);

        return back()->with('success','Leave request submitted.');
    }
}
