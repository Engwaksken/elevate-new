<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ResumeProject extends Model
{
    protected $fillable=['resume_id','name','description','url','start_date','end_date'];
    protected $casts=['start_date'=>'date','end_date'=>'date'];
}
