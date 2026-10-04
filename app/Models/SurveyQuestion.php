<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SurveyQuestion extends Model {
    protected $fillable=['survey_id','survey_section_id','question_type','question_text','hint','marks','correct_answer','options','validation_rules','conditional_logic','is_required','position'];
    protected $casts=['options'=>'array','correct_answer'=>'array','validation_rules'=>'array','conditional_logic'=>'array','is_required'=>'boolean','marks'=>'decimal:2'];
    public function survey(){ return $this->belongsTo(Survey::class); }
    public function section(){ return $this->belongsTo(SurveySection::class,'survey_section_id'); }
}