<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CalendarEvent extends Model
{
    protected $fillable=['eventable_type','eventable_id','title','event_type','description','venue','meeting_link','starts_at','ends_at','all_day','responsible_user_id','programme_id','project_id','cohort_id','status'];
    protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime','all_day'=>'boolean'];
}
