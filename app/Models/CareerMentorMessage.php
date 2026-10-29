<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareerMentorMessage extends Model
{
    protected $fillable = ['user_id', 'role', 'content', 'source', 'saved_at'];

    protected $casts = ['saved_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(CareerActionItem::class);
    }

    public function toChatArray(): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content,
            'source' => $this->source,
            'saved' => $this->saved_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
            'actions' => $this->relationLoaded('actions') ? $this->actions->map->toChatArray()->values()->all() : [],
        ];
    }
}
