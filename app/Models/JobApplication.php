<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $fillable = [
        'job_id','user_id','resume_id','cover_letter','status','applied_at'
    ];
    protected $casts = ['applied_at'=>'datetime'];

    public function job(){ return $this->belongsTo(Job::class); }
    public function user(){ return $this->belongsTo(User::class); }
    public function interviews(){ return $this->hasMany(JobInterview::class); }
    public function offers(){ return $this->hasMany(JobOffer::class); }
}
