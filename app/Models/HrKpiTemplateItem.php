<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrKpiTemplateItem extends Model
{
    protected $fillable=['hr_kpi_template_id','item_type','section','title','weight','position','meta'];
    protected $casts=['weight'=>'decimal:2','meta'=>'array'];

    public function template(){ return $this->belongsTo(HrKpiTemplate::class,'hr_kpi_template_id'); }
}
