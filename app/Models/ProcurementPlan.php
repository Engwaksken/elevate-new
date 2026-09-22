<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProcurementPlan extends Model
{
    protected $fillable=['programme_id','project_id','workplan_id','financial_year','title','description','estimated_budget','currency','status','created_by','approved_by','approved_at'];
    protected $casts=['estimated_budget'=>'decimal:2','approved_at'=>'datetime'];
}
