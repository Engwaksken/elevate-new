<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SurveyQuestion extends Model {
    protected $fillable=['survey_id','survey_section_id','question_type','question_text','hint','options','validation_rules','conditional_logic','is_required','position'];
    protected $casts=['options'=>'array','validation_rules'=>'array','conditional_logic'=>'array','is_required'=>'boolean'];
    public function survey(){ return $this->belongsTo(Survey::class); }
    public function section(){ return $this->belongsTo(SurveySection::class,'survey_section_id'); }
}