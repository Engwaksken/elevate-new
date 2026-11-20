<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/** A job opening HR/admins post for external candidates (careers page + embeddable widget). */
class RecruitmentVacancy extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft', 'open', 'closed', 'filled'];
    public const EDUCATION_LEVELS = [
        'none' => 'No formal requirement',
        'certificate' => 'Certificate',
        'diploma' => 'Diploma',
        'bachelors' => "Bachelor's degree",
        'masters' => "Master's degree",
        'phd' => 'PhD / Doctorate',
    ];
    public const EMPLOYMENT_TYPES = ['Full-time', 'Part-time', 'Contract', 'Consultancy', 'Internship', 'Volunteer'];

    protected $fillable = [
        'title', 'slug', 'reference', 'department_id', 'location', 'employment_type', 'salary_range', 'positions',
        'summary', 'description', 'responsibilities', 'requirements', 'min_education', 'min_experience_years',
        'skills', 'criteria', 'application_questions', 'closing_date', 'status', 'cv_required', 'published_at', 'created_by',
    ];

    protected $attributes = ['status' => 'draft', 'cv_required' => true, 'positions' => 1];

    protected $casts = [
        'skills' => 'array', 'criteria' => 'array', 'application_questions' => 'array',
        'closing_date' => 'date', 'published_at' => 'datetime', 'cv_required' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $vacancy) {
            if (blank($vacancy->slug)) {
                $base = Str::slug(Str::limit($vacancy->title, 80, '')) ?: 'job';
                $slug = $base;
                while (static::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = $base.'-'.Str::lower(Str::random(5));
                }
                $vacancy->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function department() { return $this->belongsTo(Department::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function applicants() { return $this->hasMany(RecruitmentApplicant::class, 'vacancy_id'); }
    public function questions() { return $this->hasMany(RecruitmentInterviewQuestion::class, 'vacancy_id')->orderBy('position')->orderBy('id'); }
    public function interviews() { return $this->hasMany(RecruitmentInterview::class, 'vacancy_id'); }

    /** Open, published and not past its closing date. */
    public function scopeAcceptingApplications($query)
    {
        return $query->where('status', 'open')
            ->where(fn ($q) => $q->whereNull('closing_date')->orWhereDate('closing_date', '>=', now()->toDateString()));
    }

    public function isAcceptingApplications(): bool
    {
        return $this->status === 'open' && (! $this->closing_date || $this->closing_date->endOfDay()->isFuture());
    }

    /** Criteria with weights normalised to positive integers. */
    public function screeningCriteria(): array
    {
        return collect($this->criteria ?? [])
            ->filter(fn ($c) => filled($c['label'] ?? null))
            ->map(fn ($c) => [
                'label' => trim((string) $c['label']),
                'weight' => max(1, min(10, (int) ($c['weight'] ?? 1))),
                'must_have' => (bool) ($c['must_have'] ?? false),
            ])->values()->all();
    }

    public function publicUrl(): string
    {
        return route('careers.show', $this->slug);
    }
}
