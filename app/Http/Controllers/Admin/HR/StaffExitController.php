<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\StaffExit;
use App\Models\User;
use App\Services\ExitClearanceService;
use Illuminate\Http\Request;

class StaffExitController extends Controller
{
    public function index(Request $request)
    {
        $query=StaffExit::with(['employee.user','clearances'])->latest();

        if($status=$request->get('status')) $query->where('status',$status);

        $perPage=in_array((int)$request->get('per_page'),[10,25,50,100],true)
            ? (int)$request->get('per_page') : 25;

        return view('admin.hr.exits.index',[
            'exits'=>$query->paginate($perPage)->withQueryString(),
            'employees'=>Employee::with('user')->whereIn('status',['active','probation','on_leave'])->get(),
            'users'=>User::where('user_type','staff')->orderBy('name')->get(),
            'stats'=>[
                'total'=>StaffExit::count(),
                'initiated'=>StaffExit::where('status','initiated')->count(),
                'completed'=>StaffExit::where('status','completed')->count(),
                'exiting'=>Employee::where('status','exiting')->count(),
            ],
        ]);
    }

    public function store(Request $request, ExitClearanceService $service)
    {
        $data=$request->validate([
            'employee_id'=>['required','exists:employees,id'],
            'exit_type'=>['required','in:resignation,end_of_contract,termination,retirement,transfer,new_organisation,other'],
            'notice_date'=>['nullable','date'],
            'last_working_date'=>['required','date'],
            'reason'=>['nullable','string'],
            'destination_organisation'=>['nullable','string','max:190'],
            'new_role'=>['nullable','string','max:190'],
            'destination_sector'=>['nullable','string','max:190'],
            'handover_user_id'=>['nullable','exists:users,id'],
        ]);

        $exit=StaffExit::create($data+['status'=>'initiated']);
        $service->createDefaultClearances($exit);
        $exit->employee()->update(['status'=>'exiting']);

        return back()->with('success','Staff exit process started.');
    }

    public function clear(Request $request, StaffExit $exit)
    {
        $clearance=$exit->clearances()->findOrFail($request->integer('clearance_id'));

        $clearance->update([
            'status'=>'cleared',
            'remarks'=>$request->input('remarks'),
            'cleared_at'=>now(),
            'cleared_by'=>auth()->id(),
        ]);

        return back()->with('success','Clearance completed.');
    }

    public function complete(StaffExit $exit, ExitClearanceService $service)
    {
        abort_unless(
            $service->canComplete($exit),
            422,
            'Outstanding clearance or handover items remain.'
        );

        $exit->update(['status'=>'completed']);
        $exit->employee()->update(['status'=>'exited']);
        $exit->employee->user()->update(['status'=>'inactive']);

        return back()->with('success','Staff exit completed and account deactivated.');
    }
}
