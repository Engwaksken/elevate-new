<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrKpiTemplate extends Model
{
    protected $fillable=['name','source_file','source_sheet','template_type','quarter','year','uploaded_by','is_active'];
    protected $casts=['is_active'=>'boolean'];

    public function items(){ return $this->hasMany(HrKpiTemplateItem::class)->orderBy('position'); }
    public function uploader(){ return $this->belongsTo(User::class,'uploaded_by'); }
}
