<?php
namespace App\Http\Controllers\Admin\Procurement;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    use ExportsTables;
    public function index(Request $request, PurchaseRequest $purchaseRequest)
    {
        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'Quotations - '.$purchaseRequest->request_number,$purchaseRequest->quotations()->with('supplier')->latest(),[
                'Supplier'=>'supplier.name',
                'Quotation No.'=>'quotation_number',
                'Date'=>'quotation_date',
                'Valid Until'=>'valid_until',
                'Subtotal'=>fn($q)=>number_format((float)$q->subtotal,2),
                'Tax'=>fn($q)=>number_format((float)$q->tax_amount,2),
                'Total'=>fn($q)=>number_format((float)$q->total_amount,2),
                'Currency'=>'currency',
                'Status'=>fn($q)=>str_replace('_',' ',(string)$q->status),
            ],['Purchase Request'=>$purchaseRequest->request_number]);
        }

        return view('admin.procurement.quotations.index',[
            'purchaseRequest'=>$purchaseRequest->load('quotations.supplier'),
            'suppliers'=>Supplier::where('status','approved')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, PurchaseRequest $purchaseRequest)
    {
        $data=$request->validate([
            'supplier_id'=>['required','exists:suppliers,id'],
            'quotation_number'=>['nullable','string','max:100'],
            'quotation_date'=>['nullable','date'],
            'valid_until'=>['nullable','date'],
            'subtotal'=>['required','numeric','min:0'],
            'tax_amount'=>['nullable','numeric','min:0'],
            'total_amount'=>['required','numeric','min:0'],
            'currency'=>['required','string','size:3'],
        ]);

        $purchaseRequest->quotations()->create($data);
        $purchaseRequest->update(['status'=>'sourcing']);

        return back()->with('success','Quotation recorded.');
    }

    public function evaluate(Request $request, \App\Models\Quotation $quotation)
    {
        $data=$request->validate([
            'technical_score'=>['nullable','numeric','min:0','max:100'],
            'financial_score'=>['nullable','numeric','min:0','max:100'],
            'overall_score'=>['nullable','numeric','min:0','max:100'],
            'comments'=>['nullable','string'],
            'recommended'=>['nullable','boolean'],
        ]);

        $quotation->evaluation()->updateOrCreate([],[
            ...$data,
            'recommended'=>$request->boolean('recommended'),
            'evaluated_by'=>auth()->id(),
        ]);

        $quotation->update(['status'=>'evaluated']);

        return back()->with('success','Quotation evaluated.');
    }
}
