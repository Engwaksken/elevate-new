<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CourseApplication extends Model {
    protected $fillable=['course_call_id','user_id','assessment_attempt_id','status','application_score','entry_assessment_score','reviewer_comments','reviewed_by','submitted_at','reviewed_at','enrolled_at'];
    protected $casts=['application_score'=>'decimal:2','entry_assessment_score'=>'decimal:2','submitted_at'=>'datetime','reviewed_at'=>'datetime','enrolled_at'=>'datetime'];
    public function courseCall(){ return $this->belongsTo(CourseCall::class); }
    public function user(){ return $this->belongsTo(User::class); }
    public function answers(){ return $this->hasMany(CourseApplicationAnswer::class); }
    public function assessmentAttempt(){ return $this->belongsTo(AssessmentAttempt::class); }
    public function reviewer(){ return $this->belongsTo(User::class,'reviewed_by'); }
}