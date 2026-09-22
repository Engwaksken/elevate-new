<?php
namespace App\Http\Controllers\Admin\Procurement;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function index(PurchaseRequest $purchaseRequest)
    {
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
