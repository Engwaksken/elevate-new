<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AppraisalObjective extends Model
{
    protected $fillable=['appraisal_id','workplan_id','milestone_id','activity_id','title','expected_result','weight','self_rating','manager_rating','employee_evidence','manager_feedback'];
    protected $casts=['weight'=>'decimal:2','self_rating'=>'decimal:2','manager_rating'=>'decimal:2'];
}
