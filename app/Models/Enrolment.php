<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrolment extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Enrolment $enrolment) {
            $enrolment->enrolment_code = app(\App\Services\EnrolmentIdentityService::class)->allocate($enrolment);
        });
        static::created(function (Enrolment $enrolment) {
            app(\App\Services\EnrolmentIdentityService::class)->assignParticipantCode($enrolment);
        });
        static::updating(function (Enrolment $enrolment) {
            // Enrollment IDs remain permanent even when status or cohort is edited.
            $enrolment->enrolment_code = $enrolment->getOriginal('enrolment_code');
        });
    }

    protected $fillable = [
        'course_id','user_id','cohort_id','status','enrolled_at',
        'started_at','completed_at','progress_percent','final_score',
        'source_type','source_id'
    ];
    protected $casts = [
        'enrolled_at'=>'datetime','started_at'=>'datetime','completed_at'=>'datetime',
        'progress_percent'=>'decimal:2','final_score'=>'decimal:2'
    ];

    public function course(){ return $this->belongsTo(Course::class); }
    public function user(){ return $this->belongsTo(User::class); }
    public function cohort(){ return $this->belongsTo(Cohort::class); }
}
