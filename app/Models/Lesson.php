<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'course_module_id','title','content','content_type','video_url',
        'external_url','file_path','estimated_minutes','position','is_published'
    ];
    protected $casts = ['is_published'=>'boolean'];

    public function module(){ return $this->belongsTo(CourseModule::class,'course_module_id'); }
}
