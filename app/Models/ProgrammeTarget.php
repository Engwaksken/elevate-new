<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgrammeTarget extends Model
{
    protected $fillable = [
        'programme_id', 'project_id', 'cohort_id', 'name', 'description', 'result_area',
        'unit', 'baseline_value', 'target_value', 'achieved_value', 'weight',
        'progress_percent', 'start_date', 'end_date', 'status', 'responsible_user_id',
        'created_by', 'achieved_at',
    ];

    protected $casts = [
        'baseline_value' => 'decimal:4',
        'target_value' => 'decimal:4',
        'achieved_value' => 'decimal:4',
        'weight' => 'decimal:2',
        'progress_percent' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'achieved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (ProgrammeTarget $target) {
            $target->applyProgress();
        });
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function applyProgress(): void
    {
        $targetValue = (float) ($this->target_value ?? 0);
        $achieved = (float) ($this->achieved_value ?? 0);

        if ($targetValue > 0) {
            $this->progress_percent = round(max(0, min(100, ($achieved / $targetValue) * 100)), 2);
        }

        $progress = max(0, min(100, (float) ($this->progress_percent ?? 0)));

        if ($this->status === 'cancelled') {
            return;
        }

        if ($this->status === 'achieved' || $progress >= 100) {
            $this->progress_percent = 100;
            $this->status = 'achieved';
            $this->achieved_at ??= now();
            return;
        }

        $this->achieved_at = null;

        $overdue = $this->end_date && $this->end_date->isPast() && $progress < 100;
        $this->status = $overdue ? 'at_risk' : ($progress > 0 ? 'in_progress' : 'not_started');
    }

    public function scopeForProgramme($query, int $programmeId)
    {
        return $query->where('programme_id', $programmeId);
    }
}
