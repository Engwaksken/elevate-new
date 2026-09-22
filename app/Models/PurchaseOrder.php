<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseOrder extends Model
{
    protected $fillable=['po_number','purchase_request_id','supplier_id','order_date','expected_delivery_date','subtotal','tax_amount','total_amount','currency','status','created_by'];
    protected $casts=['order_date'=>'date','expected_delivery_date'=>'date','subtotal'=>'decimal:2','tax_amount'=>'decimal:2','total_amount'=>'decimal:2'];
    public function items(){ return $this->hasMany(PurchaseOrderItem::class); }
    public function supplier(){ return $this->belongsTo(Supplier::class); }
}
