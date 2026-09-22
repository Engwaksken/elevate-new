<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseRequestApproval extends Model
{
    public $timestamps=false;
    protected $fillable=['purchase_request_id','user_id','approval_stage','decision','comments','acted_at'];
    protected $casts=['acted_at'=>'datetime'];
}
