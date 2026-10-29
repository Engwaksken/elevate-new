<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerActionItem extends Model
{
    public const SUGGESTED = 'suggested';
    public const APPROVED = 'approved';
    public const DISMISSED = 'dismissed';
    public const COMPLETED = 'completed';

    /** Calendar event type for approved career steps; visible only to their owner. */
    public const CALENDAR_TYPE = 'career_action';

    protected $fillable = [
        'user_id', 'career_mentor_message_id', 'participant_goal_id', 'calendar_event_id',
        'title', 'due_on', 'status', 'approved_at', 'completed_at',
    ];

    protected $casts = ['due_on' => 'date', 'approved_at' => 'datetime', 'completed_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(CareerMentorMessage::class, 'career_mentor_message_id');
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(ParticipantGoal::class, 'participant_goal_id');
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }

    public function toChatArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'due_on' => $this->due_on?->format('Y-m-d'),
            'due_label' => $this->due_on?->format('d M Y'),
            'status' => $this->status,
            'goal' => $this->goal?->title,
        ];
    }
}
