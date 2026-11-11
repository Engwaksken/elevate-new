<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantMessage extends Model
{
    protected $fillable = ['conversation_id', 'sender_id', 'body'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ParticipantConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
