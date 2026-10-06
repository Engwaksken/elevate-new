<?php
namespace App\Http\Controllers\HR;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreLeaveRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    use ExportsTables;
    public function index(Request $request)
    {
        $employee=Employee::where('user_id',auth()->id())->firstOrFail();

        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'My Leave Requests',$employee->leaveRequests()->with('leaveType')->latest(),[
                'Leave Type'=>'leaveType.name',
                'Start Date'=>'start_date',
                'End Date'=>'end_date',
                'Days'=>'days_requested',
                'Reason'=>'reason',
                'Status'=>fn($r)=>str_replace('_',' ',(string)$r->status),
                'Decision Notes'=>'decision_notes',
                'Submitted'=>'created_at',
            ],['Employee'=>$employee->employee_number]);
        }

        return view('hr.leave',[
            'employee'=>$employee,
            'leaveTypes'=>LeaveType::where('is_active',true)->orderBy('name')->get(),
            'requests'=>$employee->leaveRequests()->with('leaveType')->latest()->paginate(20),
        ]);
    }

    public function store(StoreLeaveRequest $request, LeaveService $service)
    {
        $employee=Employee::where('user_id',auth()->id())->firstOrFail();

        $data=$request->validated();

        $data['days_requested']=$service->workingDays($data['start_date'],$data['end_date']);

        $employee->leaveRequests()->create($data+['status'=>'submitted']);

        return back()->with('success','Leave request submitted.');
    }
}
