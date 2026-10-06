<?php
namespace App\Http\Controllers\HR;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreLeaveRequest;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    use ExportsTables;
    public function index(Request $request)
    {
        $employee=Employee::where('user_id',auth()->id())->first();

        if($employee && $format=$this->exportFormat($request)){
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

        $leaveTypes=LeaveType::where('is_active',true)->orderBy('name')->get();
        $year=now()->year;

        // Days left per leave type this year: the HR balance when one is set,
        // otherwise the type's default allowance minus approved/pending days.
        $balances=collect();
        if($employee){
            $recorded=LeaveBalance::where('employee_id',$employee->id)->where('year',$year)->get()->keyBy('leave_type_id');
            $taken=$employee->leaveRequests()
                ->whereYear('start_date',$year)
                ->whereNotIn('status',['rejected','cancelled'])
                ->selectRaw('leave_type_id, sum(days_requested) as days')
                ->groupBy('leave_type_id')->pluck('days','leave_type_id');
            $balances=$leaveTypes->mapWithKeys(fn($type)=>[$type->id=>$recorded->has($type->id)
                ? (float)$recorded[$type->id]->remaining
                : max(0,(float)$type->default_days-(float)($taken[$type->id] ?? 0))]);
        }

        return view('hr.leave',[
            'employee'=>$employee,
            'leaveTypes'=>$leaveTypes,
            'balances'=>$balances,
            'requests'=>$employee
                ? $employee->leaveRequests()->with('leaveType')->latest()->paginate(15)
                : null,
            'stats'=>$employee ? [
                'pending'=>$employee->leaveRequests()->whereIn('status',['submitted','supervisor_approved'])->count(),
                'approved'=>$employee->leaveRequests()->where('status','hr_approved')->whereYear('start_date',$year)->count(),
                'days'=>(float)$employee->leaveRequests()->where('status','hr_approved')->whereYear('start_date',$year)->sum('days_requested'),
            ] : null,
        ]);
    }

    public function store(StoreLeaveRequest $request, LeaveService $service)
    {
        $employee=Employee::where('user_id',auth()->id())->first();

        if(!$employee){
            return back()->withInput()->with('error','Your staff record is not set up yet, so leave cannot be requested. Please ask HR to add you as an employee.');
        }

        $data=$request->validated();

        $data['days_requested']=$service->workingDays($data['start_date'],$data['end_date']);

        $employee->leaveRequests()->create($data+['status'=>'submitted']);

        return redirect()->route('hr.leave.index')->with('success','Leave request submitted for approval.');
    }
}
