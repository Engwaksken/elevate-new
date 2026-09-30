<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class AppraisalKra extends Model {protected $fillable=['appraisal_id','title','description','expected_result','actual_result','weight','employee_rating','supervisor_rating','agreed_rating','employee_comment','supervisor_comment','position']; public function appraisal(){return $this->belongsTo(Appraisal::class);} public function kpis(){return $this->hasMany(AppraisalKpi::class)->orderBy('position');}}
