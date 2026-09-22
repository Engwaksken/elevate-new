<?php
namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        $employee->contracts()->create($request->validate([
            'contract_type'=>['nullable','string','max:100'],
            'start_date'=>['required','date'],
            'end_date'=>['nullable','date','after_or_equal:start_date'],
            'gross_salary'=>['nullable','numeric','min:0'],
            'currency'=>['nullable','string','size:3'],
            'status'=>['required','in:draft,active,expired,terminated'],
        ]));
        return back()->with('success','Contract added.');
    }
}
