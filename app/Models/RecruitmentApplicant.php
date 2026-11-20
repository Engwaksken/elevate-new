<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** An external job seeker's application to a recruitment vacancy. */
class RecruitmentApplicant extends Model
{
    public const STATUSES = ['new', 'screened', 'shortlisted', 'interview', 'offered', 'hired', 'rejected', 'withdrawn'];

    protected $fillable = [
        'vacancy_id', 'reference', 'name', 'email', 'phone', 'location', 'education_level', 'years_experience',
        'linkedin_url', 'cover_letter', 'answers', 'cv_path', 'cv_original_name', 'cv_text', 'source', 'source_site',
        'status', 'ai_score', 'ai_recommendation', 'ai_summary', 'ai_breakdown', 'ai_method', 'ai_screened_at',
        'hr_notes', 'shortlisted_by', 'shortlisted_at',
    ];

    protected $casts = [
        'answers' => 'array', 'ai_breakdown' => 'array', 'ai_screened_at' => 'datetime',
        'shortlisted_at' => 'datetime', 'ai_score' => 'decimal:2',
    ];

    protected $hidden = ['cv_text'];

    protected $attributes = ['status' => 'new', 'source' => 'careers'];

    protected static function booted(): void
    {
        static::creating(function (self $applicant) {
            if (blank($applicant->reference)) {
                do {
                    $ref = 'APP-'.now()->format('ym').'-'.Str::upper(Str::random(6));
                } while (static::where('reference', $ref)->exists());
                $applicant->reference = $ref;
            }
        });
    }

    public function vacancy() { return $this->belongsTo(RecruitmentVacancy::class, 'vacancy_id'); }
    public function interviews() { return $this->hasMany(RecruitmentInterview::class, 'applicant_id')->latest('scheduled_at'); }
    public function shortlister() { return $this->belongsTo(User::class, 'shortlisted_by'); }
}
