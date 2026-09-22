<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Task extends Model
{
    protected $fillable=['activity_id','milestone_id','title','description','assigned_to','start_date','due_date','priority','status','progress_percent','reminder_at','escalation_at'];
    protected $casts=['start_date'=>'date','due_date'=>'date','reminder_at'=>'datetime','escalation_at'=>'datetime','progress_percent'=>'decimal:2'];
}
