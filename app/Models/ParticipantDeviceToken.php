<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipantDeviceToken extends Model
{
    protected $fillable = [
        'user_id','device_id','token','platform','app_version','last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
