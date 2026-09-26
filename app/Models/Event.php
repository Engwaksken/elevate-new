<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable=[
        'title','event_type','description','starts_at','ends_at','venue','district',
        'delivery_mode','meeting_url','capacity','registration_required','is_published',
        'cohort_id','course_id','created_by','checkin_token','feedback_enabled',
        'certificate_enabled','certificate_requires_feedback','certificate_title',
        'certificate_signatory_name','certificate_signatory_title'
    ];

    protected $casts=[
        'starts_at'=>'datetime','ends_at'=>'datetime','registration_required'=>'boolean',
        'is_published'=>'boolean','feedback_enabled'=>'boolean','certificate_enabled'=>'boolean',
        'certificate_requires_feedback'=>'boolean',
    ];

    public function registrations(){ return $this->hasMany(EventRegistration::class); }
    public function attendanceRecords(){ return $this->hasMany(EventAttendanceRecord::class); }
    public function reminders(){ return $this->hasMany(EventReminder::class); }
    public function feedbackResponses(){ return $this->hasMany(EventFeedbackResponse::class); }
    public function certificates(){ return $this->hasMany(EventCertificate::class); }
    public function cohort(){ return $this->belongsTo(Cohort::class); }
    public function course(){ return $this->belongsTo(Course::class); }
    public function creator(){ return $this->belongsTo(User::class,'created_by'); }
}
