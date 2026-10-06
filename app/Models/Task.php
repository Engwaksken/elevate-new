<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    public const OPEN_STATUSES = ['not_started', 'in_progress', 'returned_for_revision', 'overdue'];

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    protected $fillable=[
        'activity_id',
        'milestone_id',
        'appraisal_kpi_id',
        'staff_kpi_id',
        'instructor_appointment_id',
        'title',
        'description',
        'assigned_to',
        'created_by',
        'start_date',
        'due_date',
        'priority',
        'status',
        'progress_percent',
        'completed_at',
        'outcome',
        'reminder_at',
        'escalation_at',
    ];

    protected $casts=[
        'start_date'=>'date',
        'due_date'=>'date',
        'completed_at'=>'datetime',
        'reminder_at'=>'datetime',
        'escalation_at'=>'datetime',
        'progress_percent'=>'decimal:2',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class,'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class,'created_by');
    }

    public function kpi()
    {
        return $this->belongsTo(AppraisalKpi::class,'appraisal_kpi_id');
    }

    public function appointment()
    {
        return $this->belongsTo(InstructorAppointment::class, 'instructor_appointment_id');
    }

    public function staffKpi()
    {
        return $this->belongsTo(StaffKpi::class);
    }

    /**
     * Title of whichever KPI the task is linked to (contract KPI first).
     */
    public function kpiTitle(): ?string
    {
        return $this->staffKpi?->title ?? $this->kpi?->title;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    /**
     * Overdue is worked out from the due date rather than stored, so it is always current.
     */
    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_date !== null && $this->due_date->lt(today());
    }
}
