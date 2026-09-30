<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentExtensionRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'assessment_id', 'user_id', 'reason', 'requested_due_at', 'status',
        'reviewed_by', 'reviewed_at', 'reviewer_note', 'approved_due_at',
    ];

    protected $casts = [
        'requested_due_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_due_at' => 'datetime',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Mobile API shape.
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'assessment_id' => $this->assessment_id,
            'status' => $this->status,
            'reason' => $this->reason,
            'requested_due_at' => $this->requested_due_at?->toIso8601String(),
            'approved_due_at' => $this->approved_due_at?->toIso8601String(),
            'reviewer_note' => $this->reviewer_note,
            'created_at' => $this->created_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
        ];
    }
}
