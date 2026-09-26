<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CourseCallQuestion extends Model {
    protected $fillable=['course_call_id','question_type','question_text','options','is_required','position'];
    protected $casts=['options'=>'array','is_required'=>'boolean'];
    public function courseCall(){ return $this->belongsTo(CourseCall::class); }
}