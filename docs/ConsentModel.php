<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consent extends Model
{
    protected $fillable = [
        'user_id','consent_type','policy_version','accepted','accepted_at','ip_address','metadata'
    ];

    protected $casts = [
        'accepted' => 'boolean',
        'accepted_at' => 'datetime',
        'metadata' => 'array',
    ];
}
