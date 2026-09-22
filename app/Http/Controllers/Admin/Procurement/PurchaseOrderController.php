<?php
namespace App\Http\Controllers\Admin\Procurement;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Services\PurchaseOrderNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return view('admin.procurement.purchase-orders.index',[
            'orders'=>PurchaseOrder::with('supplier')->latest()->paginate(20)
        ]);
    }

    public function store(Request $request, PurchaseOrderNumberService $numbers)
    {
        $data=$request->validate([
            'purchase_request_id'=>['required','exists:purchase_requests,id'],
            'supplier_id'=>['required','exists:suppliers,id'],
            'order_date'=>['required','date'],
            'expected_delivery_date'=>['nullable','date','after_or_equal:order_date'],
            'currency'=>['required','string','size:3'],
        ]);

        $pr=PurchaseRequest::with('items')->findOrFail($data['purchase_request_id']);

        DB::transaction(function() use($data,$pr,$numbers){
            $po=PurchaseOrder::create([
                ...$data,
                'po_number'=>$numbers->next(),
                'subtotal'=>$pr->estimated_total,
                'tax_amount'=>0,
                'total_amount'=>$pr->estimated_total,
                'status'=>'issued',
                'created_by'=>auth()->id(),
            ]);

            foreach($pr->items as $item){
                $po->items()->create([
                    'purchase_request_item_id'=>$item->id,
                    'item_name'=>$item->item_name,
                    'specification'=>$item->specification,
                    'quantity'=>$item->quantity,
                    'unit_price'=>$item->estimated_unit_cost ?? 0,
                    'line_total'=>$item->estimated_total ?? 0,
                    'is_asset'=>$item->is_asset,
                ]);
            }

            $pr->update(['status'=>'ordered']);
        });

        return back()->with('success','Purchase order created.');
    }
}
