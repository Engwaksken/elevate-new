<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseRequestItem extends Model
{
    protected $fillable=['purchase_request_id','item_name','specification','quantity','unit','estimated_unit_cost','estimated_total','is_asset'];
    protected $casts=['quantity'=>'decimal:2','estimated_unit_cost'=>'decimal:2','estimated_total'=>'decimal:2','is_asset'=>'boolean'];
}
