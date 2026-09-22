<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobInterview extends Model
{
    protected $fillable = [
        'job_application_id','scheduled_at','venue','meeting_link','notes','status'
    ];
    protected $casts = ['scheduled_at'=>'datetime'];
    public function application(){ return $this->belongsTo(JobApplication::class,'job_application_id'); }
}
