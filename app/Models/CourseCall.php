<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseCall extends Model
{
    protected $fillable = [
        'course_id',
        'programme_id',
        'project_id',
        'cohort_id',
        'entry_assessment_id',
        'title',
        'description',
        'eligibility_criteria',
        'available_slots',
        'opens_at',
        'closes_at',
        'status',
        'created_by',
    ];

    protected $casts = [
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
    ];

    /**
     * Legacy single-course relation retained for backwards compatibility.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * New source of truth: one general Course Call may contain many courses.
     */
    public function courses()
    {
        return $this->belongsToMany(
            Course::class,
            'course_call_course',
            'course_call_id',
            'course_id'
        )->withTimestamps();
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function cohort()
    {
        return $this->belongsTo(Cohort::class);
    }

    public function entryAssessment()
    {
        return $this->belongsTo(Assessment::class, 'entry_assessment_id');
    }

    public function questions()
    {
        return $this->hasMany(CourseCallQuestion::class)
            ->orderBy('position');
    }

    public function applications()
    {
        return $this->hasMany(CourseApplication::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'published'
            && (! $this->opens_at || $this->opens_at->lte(now()))
            && (! $this->closes_at || $this->closes_at->gte(now()));
    }
}
