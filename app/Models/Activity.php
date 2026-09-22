<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Activity extends Model
{
    protected $fillable=['workplan_id','milestone_id','programme_id','project_id','cohort_id','activity_code','title','description','location','start_date','end_date','responsible_user_id','budget','currency','funding_source','priority','status','progress_percent','expected_output','actual_output','challenges','lessons_learned','next_action'];
    protected $casts=['start_date'=>'date','end_date'=>'date','budget'=>'decimal:2','progress_percent'=>'decimal:2'];
    public function workplan(){ return $this->belongsTo(Workplan::class); }
    public function milestone(){ return $this->belongsTo(Milestone::class); }
    public function indicators(){ return $this->belongsToMany(Indicator::class)->withPivot('contribution_type')->withTimestamps(); }
    public function tasks(){ return $this->hasMany(Task::class); }
}
