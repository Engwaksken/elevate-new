<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class IndicatorTarget extends Model
{
    protected $fillable=['indicator_id','period_type','period_label','period_start','period_end','programme_id','project_id','cohort_id','branch_id','target_numeric','target_text'];
    protected $casts=['period_start'=>'date','period_end'=>'date','target_numeric'=>'decimal:4'];
}
