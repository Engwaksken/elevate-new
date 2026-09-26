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
    public function index(Request $request)
    {
        $query=PurchaseOrder::with(['supplier','items'])->latest();

        if($search=trim((string)$request->get('search'))){
            $query->where(function($q) use($search){
                $q->where('po_number','like',"%{$search}%")
                  ->orWhereHas('supplier',fn($s)=>$s->where('name','like',"%{$search}%"));
            });
        }

        if($status=$request->get('status')) $query->where('status',$status);

        $perPage=in_array((int)$request->get('per_page'),[10,20,25,50,100],true)
            ? (int)$request->get('per_page') : 20;

        return view('admin.procurement.purchase-orders.index',[
            'orders'=>$query->paginate($perPage)->withQueryString(),
            'requests'=>PurchaseRequest::with('items')
                ->whereIn('status',['approved','procurement_review'])
                ->latest()->get(),
            'suppliers'=>Supplier::where('status','approved')->orderBy('name')->get(),
            'stats'=>[
                'total'=>PurchaseOrder::count(),
                'issued'=>PurchaseOrder::where('status','issued')->count(),
                'received'=>PurchaseOrder::where('status','received')->count(),
                'value'=>PurchaseOrder::sum('total_amount'),
            ],
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
