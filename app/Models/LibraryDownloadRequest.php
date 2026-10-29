<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryDownloadRequest extends Model
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const DECLINED = 'declined';

    /** How long an approval lets the reader download the book. */
    public const APPROVAL_DAYS = 7;

    protected $fillable = [
        'user_id', 'library_resource_id', 'reason', 'status',
        'decided_by', 'decided_at', 'decision_note', 'expires_at',
    ];

    protected $casts = ['decided_at' => 'datetime', 'expires_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(LibraryResource::class, 'library_resource_id')->withTrashed();
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isUsable(): bool
    {
        return $this->status === self::APPROVED && $this->expires_at && $this->expires_at->isFuture();
    }
}
