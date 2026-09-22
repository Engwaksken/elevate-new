<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ResumeExperience extends Model
{
    protected $fillable=['resume_id','job_title','organisation','location','start_date','end_date','is_current','description','position'];
    protected $casts=['start_date'=>'date','end_date'=>'date','is_current'=>'boolean'];
}
