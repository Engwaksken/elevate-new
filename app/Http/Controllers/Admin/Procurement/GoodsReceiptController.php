<?php
namespace App\Http\Controllers\Admin\Procurement;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data=$request->validate([
            'received_date'=>['required','date'],
            'delivery_note_reference'=>['nullable','string','max:190'],
            'remarks'=>['nullable','string'],
            'items'=>['required','array'],
            'items.*.purchase_order_item_id'=>['required','exists:purchase_order_items,id'],
            'items.*.quantity_received'=>['required','numeric','min:0'],
            'items.*.quantity_accepted'=>['required','numeric','min:0'],
            'items.*.quantity_rejected'=>['nullable','numeric','min:0'],
            'items.*.condition_notes'=>['nullable','string'],
        ]);

        DB::transaction(function() use($data,$purchaseOrder){
            $receipt=GoodsReceipt::create([
                'receipt_number'=>'GRN-'.now()->format('YmdHis'),
                'purchase_order_id'=>$purchaseOrder->id,
                'received_date'=>$data['received_date'],
                'received_by'=>auth()->id(),
                'delivery_note_reference'=>$data['delivery_note_reference'] ?? null,
                'remarks'=>$data['remarks'] ?? null,
                'status'=>'confirmed',
            ]);

            foreach($data['items'] as $item){
                $receipt->items()->create($item);
            }

            $purchaseOrder->update(['status'=>'received']);
            $purchaseOrder->purchaseRequest()->update(['status'=>'received']);
        });

        return back()->with('success','Goods receipt confirmed.');
    }
}
