<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateRecommendation extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'context_type',
        'course_id',
        'event_id',
        'user_id',
        'recommended_by',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'certificate_id',
        'event_certificate_id',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recommender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function eventCertificate(): BelongsTo
    {
        return $this->belongsTo(EventCertificate::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function contextTitle(): string
    {
        return $this->context_type === 'event'
            ? ($this->event?->title ?? 'Event')
            : ($this->course?->title ?? 'Course');
    }
}
