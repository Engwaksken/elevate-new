<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Milestone extends Model
{
    protected $fillable=['workplan_id','title','description','start_date','due_date','responsible_user_id','expected_result','weight','progress_percent','status','priority','dependencies','remarks'];
    protected $casts=['start_date'=>'date','due_date'=>'date','weight'=>'decimal:2','progress_percent'=>'decimal:2'];
    public function workplan(){ return $this->belongsTo(Workplan::class); }
    public function activities(){ return $this->hasMany(Activity::class); }
}
