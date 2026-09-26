<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CohortModuleRelease extends Model
{
    protected $fillable=['course_module_id','cohort_id','is_released','released_at','released_by'];
    protected $casts=['is_released'=>'boolean','released_at'=>'datetime'];
    public function module(){ return $this->belongsTo(CourseModule::class,'course_module_id'); }
}
