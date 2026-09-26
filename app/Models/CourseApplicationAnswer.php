<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CourseApplicationAnswer extends Model {
    protected $fillable=['course_application_id','course_call_question_id','answer_text','answer_json'];
    protected $casts=['answer_json'=>'array'];
    public function application(){ return $this->belongsTo(CourseApplication::class,'course_application_id'); }
    public function question(){ return $this->belongsTo(CourseCallQuestion::class,'course_call_question_id'); }
}