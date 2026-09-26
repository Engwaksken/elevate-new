<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SurveyResponse extends Model {
    protected $fillable=['survey_id','user_id','respondent_token','status','started_at','submitted_at'];
    protected $casts=['started_at'=>'datetime','submitted_at'=>'datetime'];
    public function survey(){ return $this->belongsTo(Survey::class); }
    public function user(){ return $this->belongsTo(User::class); }
    public function answers(){ return $this->hasMany(SurveyAnswer::class); }
}