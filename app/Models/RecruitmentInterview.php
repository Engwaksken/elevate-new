<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentInterview extends Model
{
    public const MODES = ['in_person' => 'In person', 'online' => 'Online', 'phone' => 'Phone'];

    protected $fillable = [
        'applicant_id', 'vacancy_id', 'scheduled_at', 'duration_minutes', 'mode', 'location', 'meeting_link',
        'candidate_message', 'status', 'average_score', 'created_by', 'candidate_notified_at',
    ];

    protected $attributes = ['status' => 'scheduled', 'mode' => 'in_person', 'duration_minutes' => 45];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'candidate_notified_at' => 'datetime',
        'average_score' => 'decimal:2',
    ];

    public function applicant() { return $this->belongsTo(RecruitmentApplicant::class, 'applicant_id'); }
    public function vacancy() { return $this->belongsTo(RecruitmentVacancy::class, 'vacancy_id'); }
    public function panelists() { return $this->hasMany(RecruitmentInterviewPanelist::class, 'interview_id'); }

    /** Average of the submitted panel totals (percentages). */
    public function refreshAverage(): void
    {
        $avg = $this->panelists()->whereNotNull('submitted_at')->avg('total_score');
        $this->forceFill(['average_score' => $avg === null ? null : round((float) $avg, 2)])->save();
    }
}
