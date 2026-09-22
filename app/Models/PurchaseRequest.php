<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseRequest extends Model
{
    protected $fillable=['request_number','procurement_plan_id','programme_id','project_id','workplan_id','activity_id','requester_user_id','department','required_date','funding_source','justification','estimated_total','currency','status'];
    protected $casts=['required_date'=>'date','estimated_total'=>'decimal:2'];
    public function items(){ return $this->hasMany(PurchaseRequestItem::class); }
    public function approvals(){ return $this->hasMany(PurchaseRequestApproval::class); }
    public function quotations(){ return $this->hasMany(Quotation::class); }
}
