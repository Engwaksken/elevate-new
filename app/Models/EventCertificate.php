<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventCertificate extends Model
{
    protected $fillable=[
        'event_id','user_id','event_attendance_record_id','certificate_code','issued_at','issued_by'
    ];

    protected $casts=['issued_at'=>'datetime'];

    public function event(){ return $this->belongsTo(Event::class); }
    public function user(){ return $this->belongsTo(User::class); }
    public function attendance(){ return $this->belongsTo(EventAttendanceRecord::class,'event_attendance_record_id'); }
    public function issuer(){ return $this->belongsTo(User::class,'issued_by'); }
}
