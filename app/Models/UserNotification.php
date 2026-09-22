<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $fillable = ['user_id','type','title','message','action_url','data','read_at'];
    protected $casts = ['data'=>'array','read_at'=>'datetime'];
}
