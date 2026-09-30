<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class AppraisalKpi extends Model {protected $fillable=['appraisal_kra_id','title','description','measurement_method','target','actual_achievement','unit','kpi_type','weight','employee_score','supervisor_score','agreed_score','evidence','employee_comment','supervisor_comment','position']; public function kra(){return $this->belongsTo(AppraisalKra::class,'appraisal_kra_id');}}
