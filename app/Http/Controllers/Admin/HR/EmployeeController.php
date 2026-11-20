<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Admin\Concerns\BulkDeletesRecords;
use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\StaffDocument;
use App\Models\HrDocumentRequirement;
use App\Models\HrPolicyAssignment;
use App\Models\Position;
use App\Models\User;
use App\Services\HR\HrComplianceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    use ExportsTables;
    use BulkDeletesRecords;

    protected function bulkDeleteModel(): string
    {
        return Employee::class;
    }

    public function index(Request $request)
    {
        $query=Employee::with(['user','supervisor'])->latest();
        if($department=$request->get('department')) $query->where('department_id',$department);

        if($search=trim((string)$request->get('search'))){
            $query->where(function($q) use($search){
                $q->where('employee_number','like',"%{$search}%")
                    ->orWhere('employment_type','like',"%{$search}%")
                    ->orWhere('work_location','like',"%{$search}%")
                    ->orWhereIn('department_id',Department::where('name','like',"%{$search}%")->pluck('id'))
                    ->orWhereIn('position_id',Position::where('title','like',"%{$search}%")->pluck('id'))
                    ->orWhereHas('user',fn($u)=>$u
                        ->where('name','like',"%{$search}%")
                        ->orWhere('email','like',"%{$search}%"));
            });
        }

        if($status=$request->get('status')) $query->where('status',$status);

        if($format=$this->exportFormat($request)){
            $departments=Department::pluck('name','id');
            $positions=Position::pluck('title','id');

            return $this->exportTable($format,'Employees',$query,[
                'Employee'=>'user.name',
                'Email'=>'user.email',
                'Employee No.'=>'employee_number',
                'Department'=>fn($e)=>$departments[$e->department_id]??'',
                'Position'=>fn($e)=>$positions[$e->position_id]??'',
                'Employment Type'=>fn($e)=>str_replace('_',' ',(string)$e->employment_type),
                'Location'=>'work_location',
                'Start Date'=>'start_date',
                'Probation Ends'=>'probation_end_date',
                'Status'=>fn($e)=>str_replace('_',' ',(string)$e->status),
            ]);
        }

        $perPage=in_array((int)$request->get('per_page'),[10,25,50,100],true)
            ? (int)$request->get('per_page') : 25;

        $employees=$query->paginate($perPage)->withQueryString();
        $compliance=app(HrComplianceService::class)->summary($employees->getCollection()->pluck('user')->filter()->values())
            ->keyBy(fn($row)=>$row['user']->id);

        return view('admin.hr.employees.index',[
            'employees'=>$employees,
            'compliance'=>$compliance,
            'departmentNames'=>Department::pluck('name','id'),
            'positionNames'=>Position::pluck('title','id'),
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

    public function store(Request $request, HrComplianceService $compliance)
    {
        $employee=Employee::create($this->validated($request));

        $shared=$employee->user ? $compliance->onboard($employee->user,$request->user()) : 0;

        return back()->with('success','Employee record created.'.($shared ? " {$shared} onboarding polic".($shared===1?'y':'ies')." shared with them." : ''));
    }

    public function update(Request $request, Employee $employee)
    {
        $employee->update($this->validated($request,$employee));

        return back()->with('success','Employee record updated.');
    }

    public function show(Employee $employee, HrComplianceService $compliance)
    {
        $employee->load(['user','supervisor','contracts']);
        $user=$employee->user;

        return view('admin.hr.employees.show',[
            'employee'=>$employee,
            'department'=>Department::find($employee->department_id),
            'position'=>Position::find($employee->position_id),
            'documents'=>$user ? StaffDocument::with('requirement')->where('user_id',$user->id)->latest()->get() : collect(),
            'missing'=>$user ? $compliance->missingDocuments($user) : collect(),
            'policies'=>$user ? HrPolicyAssignment::with('policy')->where('user_id',$user->id)->latest()->get() : collect(),
            'requirements'=>HrDocumentRequirement::where('is_active',true)->orderBy('position')->get(),
            'users'=>User::where('user_type','staff')->orderBy('name')->get(),
            'departments'=>Department::where('is_active',true)->orderBy('name')->get(),
            'positions'=>Position::where('is_active',true)->orderBy('title')->get(),
        ]);
    }

    private function validated(Request $request, ?Employee $employee=null): array
    {
        return $request->validate([
            'user_id'=>['required',Rule::unique('employees','user_id')->ignore($employee?->id),'exists:users,id'],
            'employee_number'=>['required',Rule::unique('employees','employee_number')->ignore($employee?->id)],
            // New placements need an active department; an edit may keep the current (since deactivated) one.
            'department_id'=>['nullable',Rule::exists('departments','id')->where(fn($q)=>$q->where('is_active',true)
                ->when($employee?->department_id,fn($w,$current)=>$w->orWhere('id',$current)))],
            'position_id'=>['nullable','exists:positions,id'],
            'supervisor_user_id'=>['nullable','exists:users,id'],
            'employment_type'=>['nullable','string','max:100'],
            'work_location'=>['nullable','string','max:190'],
            'start_date'=>['nullable','date'],
            'probation_end_date'=>['nullable','date','after_or_equal:start_date'],
            'status'=>['required','in:active,probation,on_leave,suspended,exiting,exited'],
            'emergency_contact_name'=>['nullable','string','max:190'],
            'emergency_contact_phone'=>['nullable','string','max:60'],
        ]);
    }
}
