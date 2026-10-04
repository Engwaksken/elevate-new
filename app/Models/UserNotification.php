<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    protected $fillable=[
        'user_id',
        'type',
        'title',
        'message',
        'action_url',
        'tracking_token',
        'data',
        'read_at',
        'opened_at',
    ];

    protected $casts=[
        'data'=>'array',
        'read_at'=>'datetime',
        'opened_at'=>'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (UserNotification $notification) {
            $notification->tracking_token ??= bin2hex(random_bytes(24));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at'=>now()])->save();
        }
    }

    public function markOpened(): void
    {
        if ($this->opened_at === null) {
            $this->forceFill(['opened_at'=>now()])->save();
        }
    }
}
