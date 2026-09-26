<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppraisalKpiWeeklyUpdate extends Model
{
    protected $fillable=['appraisal_id','hr_kpi_template_item_id','week_number','actual_target','comment'];

    public function appraisal(){ return $this->belongsTo(Appraisal::class); }
    public function item(){ return $this->belongsTo(HrKpiTemplateItem::class,'hr_kpi_template_item_id'); }
}
