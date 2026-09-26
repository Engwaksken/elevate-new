<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SurveyAssignment extends Model {
    protected $fillable=['survey_id','user_id'];
    public function survey(){ return $this->belongsTo(Survey::class); }
    public function user(){ return $this->belongsTo(User::class); }
}