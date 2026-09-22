<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrolment extends Model
{
    protected $fillable = [
        'course_id','user_id','cohort_id','status','enrolled_at',
        'started_at','completed_at','progress_percent','final_score'
    ];
    protected $casts = [
        'enrolled_at'=>'datetime','started_at'=>'datetime','completed_at'=>'datetime',
        'progress_percent'=>'decimal:2','final_score'=>'decimal:2'
    ];

    public function course(){ return $this->belongsTo(Course::class); }
    public function user(){ return $this->belongsTo(User::class); }
    public function cohort(){ return $this->belongsTo(Cohort::class); }
}
