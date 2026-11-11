<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ParticipantConversation extends Model
{
    protected $fillable = ['type', 'title', 'created_by'];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'participant_conversation_user', 'conversation_id', 'user_id')
            ->withPivot('joined_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ParticipantMessage::class, 'conversation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ParticipantMessage::class, 'conversation_id')->latestOfMany();
    }
}
