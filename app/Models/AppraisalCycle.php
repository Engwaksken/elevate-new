<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AppraisalCycle extends Model
{
    protected $fillable=['name','cycle_type','start_date','end_date','self_assessment_due','manager_review_due','is_active'];
    protected $casts=['start_date'=>'date','end_date'=>'date','self_assessment_due'=>'date','manager_review_due'=>'date','is_active'=>'boolean'];
}
