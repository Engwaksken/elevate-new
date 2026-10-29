<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountDeletionRequest extends Model
{
    public const PENDING = 'pending';
    public const COMPLETED = 'completed';
    public const REJECTED = 'rejected';

    /** Promised on the public page and in the app. */
    public const DAYS_TO_PROCESS = 30;

    protected $fillable = ['user_id', 'name', 'email', 'reason', 'source', 'status', 'processed_by', 'processed_at', 'admin_note'];

    protected $casts = ['processed_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'requested_at' => $this->created_at?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'admin_note' => $this->status === self::REJECTED ? $this->admin_note : null,
            'days_to_process' => self::DAYS_TO_PROCESS,
        ];
    }
}
