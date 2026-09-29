<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class ParticipantDevice extends Model {
 protected $fillable=['user_id','device_id','fcm_token','platform','app_version','notifications_enabled','last_active_at'];
 protected $casts=['notifications_enabled'=>'boolean','last_active_at'=>'datetime'];
}
