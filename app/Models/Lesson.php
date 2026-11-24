<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'course_module_id','title','content','content_type','video_url',
        'external_url','file_path','estimated_minutes','position','is_published','cohort_id'
    ];
    protected $casts = ['is_published'=>'boolean'];

    public function module(){ return $this->belongsTo(CourseModule::class,'course_module_id'); }

    /** Set when the lesson is extra material for one cohort only; null means shared with the whole course. */
    public function cohort(){ return $this->belongsTo(Cohort::class); }

    /**
     * Lessons a participant in the given cohort may see: shared lessons plus
     * their own cohort's. A participant with no cohort sees shared lessons only.
     */
    public function scopeVisibleToCohort($query, ?int $cohortId)
    {
        return $query->where(fn ($q) => $q->whereNull('lessons.cohort_id')
            ->when($cohortId, fn ($q) => $q->orWhere('lessons.cohort_id', $cohortId)));
    }

    public function isVisibleToCohort(?int $cohortId): bool
    {
        return $this->cohort_id === null || ($cohortId !== null && (int) $this->cohort_id === (int) $cohortId);
    }

    /** Whether a participant may see this lesson: shared, or for the cohort they are in on this course. */
    public function isVisibleTo(User $user): bool
    {
        if ($this->cohort_id === null) {
            return true;
        }

        $courseId = $this->module?->course_id ?? CourseModule::whereKey($this->course_module_id)->value('course_id');

        return $courseId && $this->isVisibleToCohort(Enrolment::cohortIdFor($user->id, (int) $courseId));
    }

    /** Lesson material files (many per lesson). The legacy single file_path is synced into these rows. */
    public function files(){ return $this->hasMany(LearningFile::class)->orderBy('id'); }
}
