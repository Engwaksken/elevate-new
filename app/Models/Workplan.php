<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Workplan extends Model
{
    protected $fillable=['programme_id','project_id','cohort_id','title','financial_year','period_type','start_date','end_date','responsible_user_id','description','status','progress_percent','created_by','approved_by','approved_at'];
    protected $casts=['start_date'=>'date','end_date'=>'date','approved_at'=>'datetime','progress_percent'=>'decimal:2'];
    public function milestones(){ return $this->hasMany(Milestone::class); }
    public function activities(){ return $this->hasMany(Activity::class); }
}
