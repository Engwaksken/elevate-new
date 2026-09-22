<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'programme_id','project_id','branch_id','title','code','summary','description',
        'thumbnail_path','delivery_mode','start_date','end_date','duration_hours',
        'pass_mark','self_enrolment_enabled','status','created_by'
    ];

    protected $casts = [
        'start_date'=>'date','end_date'=>'date','pass_mark'=>'decimal:2',
        'self_enrolment_enabled'=>'boolean'
    ];

    public function modules(){ return $this->hasMany(CourseModule::class)->orderBy('position'); }
    public function instructors(){ return $this->belongsToMany(User::class,'course_instructors')->withPivot('is_lead')->withTimestamps(); }
    public function cohorts(){ return $this->belongsToMany(Cohort::class)->withTimestamps(); }
    public function enrolments(){ return $this->hasMany(Enrolment::class); }
    public function assessments(){ return $this->hasMany(Assessment::class); }
}
