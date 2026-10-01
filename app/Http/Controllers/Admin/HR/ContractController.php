<?php
namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmploymentContract;
use App\Services\HR\ContractSignatureService;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index(Employee $employee)
    {
        $employee->load('user');

        return view('admin.hr.employees.contracts',[
            'employee'=>$employee,
            'contracts'=>$employee->contracts()->with(['sender','signer'])->latest()->get(),
        ]);
    }

    public function store(Request $request, Employee $employee, ContractSignatureService $signatures)
    {
        $data=$request->validate([
            'contract_type'=>['nullable','string','max:100'],
            'start_date'=>['required','date'],
            'end_date'=>['nullable','date','after_or_equal:start_date'],
            'gross_salary'=>['nullable','numeric','min:0'],
            'currency'=>['nullable','string','size:3'],
            'status'=>['required','in:draft,active,expired,terminated'],
            'document'=>['nullable','file','mimes:pdf,doc,docx','max:10240'],
            'send_for_signature'=>['nullable','boolean'],
        ]);

        $contract=$employee->contracts()->create(collect($data)->except(['document','send_for_signature'])->all());

        if($request->hasFile('document')){
            $signatures->storeDocument($contract,$request->file('document'));

            if($request->boolean('send_for_signature')){
                $signatures->sendForSignature($contract,$request->user());

                return back()->with('success','Contract added and sent to the employee for signature.');
            }
        }

        return back()->with('success','Contract added.');
    }

    public function updateDocument(Request $request, EmploymentContract $contract, ContractSignatureService $signatures)
    {
        abort_if($contract->isSigned(),422,'A signed contract cannot be replaced.');

        $request->validate([
            'document'=>['required','file','mimes:pdf,doc,docx','max:10240'],
            'send_for_signature'=>['nullable','boolean'],
        ]);

        $signatures->storeDocument($contract,$request->file('document'));

        if($request->boolean('send_for_signature') || $contract->isAwaitingSignature()){
            $signatures->sendForSignature($contract->refresh(),$request->user());

            return back()->with('success','Contract file updated and sent to the employee for signature.');
        }

        return back()->with('success','Contract file updated.');
    }

    public function send(Request $request, EmploymentContract $contract, ContractSignatureService $signatures)
    {
        $signatures->sendForSignature($contract,$request->user());

        return back()->with('success','Contract sent to the employee for signature.');
    }

    public function document(Request $request, EmploymentContract $contract, ContractSignatureService $signatures)
    {
        return $signatures->documentResponse($request,$contract);
    }

    public function signature(EmploymentContract $contract, ContractSignatureService $signatures)
    {
        return $signatures->signatureResponse($contract);
    }

    public function certificate(Request $request, EmploymentContract $contract, ContractSignatureService $signatures)
    {
        return $signatures->certificateResponse($request,$contract);
    }
}
