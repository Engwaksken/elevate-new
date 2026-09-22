<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Deliverable extends Model
{
    protected $fillable=['activity_id','milestone_id','title','description','owner_user_id','due_date','status','progress_percent'];
    protected $casts=['due_date'=>'date','progress_percent'=>'decimal:2'];
}
