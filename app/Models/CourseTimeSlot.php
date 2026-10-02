<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseTimeSlot extends Model
{
    protected $fillable = [
        'course_id', 'created_by', 'title', 'starts_at', 'ends_at', 'timezone',
        'venue', 'meeting_link', 'notes', 'status',
    ];

    protected $casts = ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];

    protected function asDateTime($value)
    {
        // Timetable timestamps are always stored in UTC, independently of the
        // server's configured timezone. Convert only when presenting a session.
        return $value instanceof \DateTimeInterface
            ? CarbonImmutable::instance($value)->utc()
            : CarbonImmutable::parse($value, 'UTC');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
