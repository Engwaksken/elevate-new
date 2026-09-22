<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Appraisal extends Model
{
    protected $fillable=['appraisal_cycle_id','employee_id','manager_user_id','status','self_score','manager_score','final_score','employee_comments','manager_comments','development_plan','employee_acknowledged_at'];
    protected $casts=['self_score'=>'decimal:2','manager_score'=>'decimal:2','final_score'=>'decimal:2','employee_acknowledged_at'=>'datetime'];
    public function employee(){ return $this->belongsTo(Employee::class); }
    public function objectives(){ return $this->hasMany(AppraisalObjective::class); }
}
