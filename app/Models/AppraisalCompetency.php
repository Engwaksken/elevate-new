<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class AppraisalCompetency extends Model {protected $fillable=['appraisal_id','name','weight','employee_rating','supervisor_rating','agreed_rating','employee_comment','supervisor_comment']; public function appraisal(){return $this->belongsTo(Appraisal::class);}}
