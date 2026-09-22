<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Quotation extends Model
{
    protected $fillable=['purchase_request_id','supplier_id','quotation_number','quotation_date','valid_until','subtotal','tax_amount','total_amount','currency','document_path','status'];
    protected $casts=['quotation_date'=>'date','valid_until'=>'date','subtotal'=>'decimal:2','tax_amount'=>'decimal:2','total_amount'=>'decimal:2'];
    public function supplier(){ return $this->belongsTo(Supplier::class); }
    public function items(){ return $this->hasMany(QuotationItem::class); }
}
