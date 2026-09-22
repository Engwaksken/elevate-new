<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    public $timestamps=false;
    protected $fillable=[
        'user_id','event_type','severity','ip_address','user_agent','context','occurred_at'
    ];
    protected $casts=['context'=>'array','occurred_at'=>'datetime'];
}
