<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query=Employee::with(['user'])->latest();

        if($search=trim((string)$request->get('search'))){
            $query->where(function($q) use($search){
                $q->where('employee_number','like',"%{$search}%")
                    ->orWhere('employment_type','like',"%{$search}%")
                    ->orWhere('work_location','like',"%{$search}%")
                    ->orWhereHas('user',fn($u)=>$u
                        ->where('name','like',"%{$search}%")
                        ->orWhere('email','like',"%{$search}%"));
            });
        }

        if($status=$request->get('status')) $query->where('status',$status);

        $perPage=in_array((int)$request->get('per_page'),[10,25,50,100],true)
            ? (int)$request->get('per_page') : 25;

        return view('admin.hr.employees.index',[
            'employees'=>$query->paginate($perPage)->withQueryString(),
            'users'=>User::where('user_type','staff')->orderBy('name')->get(),
            'departments'=>Department::where('is_active',true)->orderBy('name')->get(),
            'positions'=>Position::where('is_active',true)->orderBy('title')->get(),
            'stats'=>[
                'total'=>Employee::count(),
                'active'=>Employee::where('status','active')->count(),
                'probation'=>Employee::where('status','probation')->count(),
                'on_leave'=>Employee::where('status','on_leave')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Employee::create($request->validate([
            'user_id'=>['required','unique:employees,user_id','exists:users,id'],
            'employee_number'=>['required','unique:employees,employee_number'],
            'department_id'=>['nullable','exists:departments,id'],
            'position_id'=>['nullable','exists:positions,id'],
            'supervisor_user_id'=>['nullable','exists:users,id'],
            'employment_type'=>['nullable','string','max:100'],
            'work_location'=>['nullable','string','max:190'],
            'start_date'=>['nullable','date'],
            'probation_end_date'=>['nullable','date','after_or_equal:start_date'],
            'status'=>['required','in:active,probation,on_leave,suspended,exiting,exited'],
        ]));

        return back()->with('success','Employee record created.');
    }
}
