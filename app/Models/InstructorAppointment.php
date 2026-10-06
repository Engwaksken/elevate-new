<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A participant's request to meet one of their course instructors.
 * Business rules live in App\Services\AppointmentService.
 */
class InstructorAppointment extends Model
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const DECLINED = 'declined';
    public const PROPOSED = 'rescheduled_proposed';
    public const CANCELLED = 'cancelled';
    public const COMPLETED = 'completed';

    public const STATUSES = [
        self::PENDING, self::APPROVED, self::DECLINED, self::PROPOSED, self::CANCELLED, self::COMPLETED,
    ];

    /** Statuses that still hold a time slot for the participant. */
    public const OPEN_STATUSES = [self::PENDING, self::PROPOSED, self::APPROVED];

    public const DURATIONS = [15, 30, 45, 60];

    public const MODES = ['online' => 'Online', 'in_person' => 'In person'];

    protected $fillable = [
        'participant_user_id', 'instructor_user_id', 'course_id', 'starts_at', 'ends_at',
        'duration_minutes', 'mode', 'location', 'meeting_url', 'topic', 'details', 'status',
        'proposed_starts_at', 'proposal_note', 'decision_reason', 'decided_at',
        'cancelled_by', 'cancelled_at', 'completed_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'proposed_starts_at' => 'datetime',
        'decided_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_user_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** Appointments whose [starts_at, ends_at) range intersects the given one. */
    public function scopeOverlapping(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(fn ($q) => $q->where('participant_user_id', $user->id)
            ->orWhere('instructor_user_id', $user->id));
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::PROPOSED => 'New time proposed',
            default => ucfirst($this->status),
        };
    }

    public function modeLabel(): string
    {
        return self::MODES[$this->mode] ?? ucfirst((string) $this->mode);
    }

    public function proposedEndsAt(): ?CarbonInterface
    {
        return $this->proposed_starts_at?->copy()->addMinutes($this->duration_minutes);
    }
}
