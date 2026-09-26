<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SurveySection extends Model {
    protected $fillable=['survey_id','title','description','position'];
    public function survey(){ return $this->belongsTo(Survey::class); }
    public function questions(){ return $this->hasMany(SurveyQuestion::class)->orderBy('position'); }
}