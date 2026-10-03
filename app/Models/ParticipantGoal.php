<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantGoal extends Model
{
    protected $fillable = [
        'user_id', 'mentor_match_id', 'title', 'description', 'category', 'unit',
        'baseline_value', 'target_value', 'current_value', 'progress_percent',
        'start_date', 'target_date', 'priority', 'status', 'source', 'created_by', 'completed_at',
    ];

    protected $casts = [
        'baseline_value' => 'decimal:4',
        'target_value' => 'decimal:4',
        'current_value' => 'decimal:4',
        'progress_percent' => 'decimal:2',
        'start_date' => 'date',
        'target_date' => 'date',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (ParticipantGoal $goal) {
            $goal->applyProgress();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentorMatch(): BelongsTo
    {
        return $this->belongsTo(MentorMatch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Derive the progress percentage from the baseline/target/current values.
     * Falls back to the manually supplied progress_percent when no target is set.
     */
    public function applyProgress(): void
    {
        $target = $this->target_value !== null ? (float) $this->target_value : null;
        $current = $this->current_value !== null ? (float) $this->current_value : null;
        $baseline = (float) ($this->baseline_value ?? 0);

        if ($target !== null && $target > $baseline && $current !== null) {
            $progress = (($current - $baseline) / ($target - $baseline)) * 100;
            $this->progress_percent = round(max(0, min(100, $progress)), 2);
        }

        $progress = max(0, min(100, (float) ($this->progress_percent ?? 0)));

        if ($this->status === 'cancelled') {
            return;
        }

        if ($this->status === 'completed' || $progress >= 100) {
            $this->progress_percent = 100;
            $this->status = 'completed';
            $this->completed_at ??= now();
            return;
        }

        $this->completed_at = null;
        $this->status = $progress > 0 ? 'in_progress' : 'not_started';
    }
}
