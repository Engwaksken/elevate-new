<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentAttempt extends Model
{
    protected $fillable = [
        'assessment_id','user_id','attempt_number','client_submission_id','submission_text','submission_file_path','submission_links',
        'score','percentage','instructor_feedback','status','started_at','submitted_at',
        'graded_at','graded_by',
    ];

    protected $casts = [
        'started_at'=>'datetime',
        'submission_links'=>'array',
        'submitted_at'=>'datetime',
        'graded_at'=>'datetime',
        'score'=>'decimal:2',
        'percentage'=>'decimal:2',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /** Files the participant submitted with this attempt. */
    public function files()
    {
        return $this->hasMany(AssessmentAttemptFile::class)->orderBy('id');
    }

    public function answers()
    {
        return $this->hasMany(AssessmentAnswer::class);
    }

    /**
     * Only each participant's latest attempt per assessment (one row per
     * participant and assessment), limited to the given assessment ids.
     *
     * @param  \Illuminate\Contracts\Database\Query\Builder|array<int,int>  $assessmentIds
     */
    public function scopeLatestPerParticipant($query, $assessmentIds)
    {
        return $query
            ->whereIn('assessment_id', $assessmentIds)
            ->whereIn('id', static::query()
                ->selectRaw('MAX(id)')
                ->whereIn('assessment_id', $assessmentIds)
                ->groupBy('user_id', 'assessment_id'));
    }

    /** Adds "attempts_total": how many attempts the participant made at this assessment. */
    public function scopeWithAttemptsTotal($query)
    {
        return $query->addSelect(['attempts_total' => static::query()
            ->from('assessment_attempts as history')
            ->selectRaw('COUNT(*)')
            ->whereColumn('history.user_id', 'assessment_attempts.user_id')
            ->whereColumn('history.assessment_id', 'assessment_attempts.assessment_id')]);
    }
}
