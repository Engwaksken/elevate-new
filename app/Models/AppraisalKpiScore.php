<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppraisalKpiScore extends Model
{
    protected $fillable=[
        'appraisal_id',
        'hr_kpi_template_item_id',
        'employee_rating',
        'manager_rating',
        'agreed_rating',
        'okr_percent',
        'employee_comment',
        'manager_comment',
        'evidence_note',
        'evidence_url',
    ];

    protected $casts=[
        'employee_rating'=>'decimal:2',
        'manager_rating'=>'decimal:2',
        'agreed_rating'=>'decimal:2',
        'okr_percent'=>'decimal:2',
    ];

    public function appraisal(){ return $this->belongsTo(Appraisal::class); }
    public function item(){ return $this->belongsTo(HrKpiTemplateItem::class,'hr_kpi_template_item_id'); }
}
