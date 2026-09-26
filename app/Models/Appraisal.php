<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appraisal extends Model
{
    protected $fillable=[
        'appraisal_cycle_id',
        'employee_id',
        'manager_user_id',
        'hr_kpi_template_id',
        'status',
        'self_score',
        'manager_score',
        'final_score',
        'completion_percent',
        'performance_percent',
        'employee_comments',
        'manager_comments',
        'development_plan',
        'employee_submitted_at',
        'manager_submitted_at',
        'manager_acknowledged_at',
        'manager_acknowledgement_name',
        'employee_acknowledged_at',
        'employee_acknowledgement_name',
        'hr_finalised_at',
        'hr_finalised_by',
    ];

    protected $casts=[
        'self_score'=>'decimal:2',
        'manager_score'=>'decimal:2',
        'final_score'=>'decimal:2',
        'completion_percent'=>'decimal:2',
        'performance_percent'=>'decimal:2',
        'employee_submitted_at'=>'datetime',
        'manager_submitted_at'=>'datetime',
        'manager_acknowledged_at'=>'datetime',
        'employee_acknowledged_at'=>'datetime',
        'hr_finalised_at'=>'datetime',
    ];

    public function employee(){ return $this->belongsTo(Employee::class); }
    public function cycle(){ return $this->belongsTo(AppraisalCycle::class,'appraisal_cycle_id'); }
    public function manager(){ return $this->belongsTo(User::class,'manager_user_id'); }
    public function objectives(){ return $this->hasMany(AppraisalObjective::class); }
    public function kpiTemplate(){ return $this->belongsTo(HrKpiTemplate::class,'hr_kpi_template_id'); }
    public function kpiScores(){ return $this->hasMany(AppraisalKpiScore::class); }
    public function kpiWeeklyUpdates(){ return $this->hasMany(AppraisalKpiWeeklyUpdate::class); }
    public function finalisedBy(){ return $this->belongsTo(User::class,'hr_finalised_by'); }
}
