<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SurveyAnswer extends Model {
    protected $fillable=['survey_response_id','survey_question_id','answer_text','answer_json'];
    protected $casts=['answer_json'=>'array'];
    public function response(){ return $this->belongsTo(SurveyResponse::class,'survey_response_id'); }
    public function question(){ return $this->belongsTo(SurveyQuestion::class,'survey_question_id'); }
}