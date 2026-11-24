<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A cohort's own due date for an assessment; replaces the course-wide due_at for that cohort. */
class AssessmentCohortDeadline extends Model
{
    protected $fillable = ['assessment_id', 'cohort_id', 'due_at', 'set_by'];

    protected $casts = ['due_at' => 'datetime'];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }
}
