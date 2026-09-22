<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ResumeEducation extends Model
{
    protected $table='resume_education';
    protected $fillable=['resume_id','institution','qualification','field_of_study','start_date','end_date','description','position'];
    protected $casts=['start_date'=>'date','end_date'=>'date'];
}
