<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EventAttendanceRecord extends Model{
 protected $fillable=['event_id','event_registration_id','user_id','attendance_status','check_in_at','notes','recorded_by'];
 protected $casts=['check_in_at'=>'datetime'];
 public function registration(){return $this->belongsTo(EventRegistration::class,'event_registration_id');}
}