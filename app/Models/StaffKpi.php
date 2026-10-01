<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A KPI a staff member sets for their employment contract. It is approved by their
 * supervisor, linked to day-to-day tasks, and copied into each quarterly appraisal.
 */
class StaffKpi extends Model
{
    public const STATUSES = ['draft', 'submitted', 'approved', 'returned'];

    protected $fillable = [
        'employee_id',
        'employment_contract_id',
        'kra',
        'title',
        'description',
        'measurement_method',
        'target',
        'unit',
        'weight',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_comment',
        'position',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(EmploymentContract::class, 'employment_contract_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function appraisalKpis(): HasMany
    {
        return $this->hasMany(AppraisalKpi::class);
    }

    public function isEditableByOwner(): bool
    {
        return in_array($this->status, ['draft', 'returned'], true);
    }
}
