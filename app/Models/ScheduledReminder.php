<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledReminder extends Model
{
    protected $fillable=[
        'user_id','channel','type','title','message','action_url',
        'send_at','sent_at','status','payload','failure_reason'
    ];
    protected $casts=['send_at'=>'datetime','sent_at'=>'datetime','payload'=>'array'];
}
