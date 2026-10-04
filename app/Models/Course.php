<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;
    protected $appends = ['thumbnail_url'];

    protected $fillable = [
        'programme_id','project_id','branch_id','title','code','summary','description',
        'thumbnail_path','delivery_mode','start_date','end_date','duration_hours',
        'pass_mark','self_enrolment_enabled','entry_assessment_id','entry_survey_id','status','created_by'
    ];

    protected $casts = [
        'start_date'=>'date',
        'end_date'=>'date',
        'pass_mark'=>'decimal:2',
        'self_enrolment_enabled'=>'boolean',
    ];

    public function modules()
    {
        return $this->hasMany(CourseModule::class)->orderBy('position');
    }

    public function entryAssessment()
    {
        return $this->belongsTo(Assessment::class, 'entry_assessment_id');
    }

    public function entrySurvey()
    {
        return $this->belongsTo(Survey::class, 'entry_survey_id');
    }

    /**
     * True when the course requires an entry assessment or survey that the user
     * has not yet passed.
     */
    public function entryRequirementPendingFor(?int $userId): bool
    {
        if ($this->entry_survey_id) {
            return ! $this->entrySurveyPassedFor($userId);
        }

        return $this->entryAssessmentPendingFor($userId);
    }

    public function entrySurveyPassedFor(?int $userId): bool
    {
        if (! $this->entry_survey_id || ! $userId) {
            return false;
        }

        $passMark = (float) ($this->pass_mark ?? 0);

        return SurveyResponse::query()
            ->where('survey_id', $this->entry_survey_id)
            ->where('user_id', $userId)
            ->where('status', 'submitted')
            ->whereNotNull('percentage')
            ->where('percentage', '>=', $passMark)
            ->exists();
    }

    /**
     * True when the course requires an entry assessment and the given user has
     * not yet passed it. The course pass mark is the entry threshold.
     */
    public function entryAssessmentPendingFor(?int $userId): bool
    {
        return $this->requiresEntryAssessment() && ! $this->entryAssessmentPassedFor($userId);
    }

    public function requiresEntryAssessment(): bool
    {
        return (bool) $this->entry_assessment_id;
    }

    /**
     * True when the user has a graded entry-assessment attempt at or above the
     * course pass mark.
     */
    public function entryAssessmentPassedFor(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        $passMark = (float) ($this->pass_mark ?? 0);

        return AssessmentAttempt::query()
            ->where('assessment_id', $this->entry_assessment_id)
            ->where('user_id', $userId)
            ->where('status', 'graded')
            ->whereNotNull('percentage')
            ->where('percentage', '>=', $passMark)
            ->exists();
    }

    public function instructors()
    {
        return $this->belongsToMany(User::class,'course_instructors','course_id','user_id')
            ->withPivot('is_lead')
            ->withTimestamps();
    }

    /**
     * Courses a participant is automatically enrolled in when enrolled here.
     */
    public function compulsoryCourses()
    {
        return $this->belongsToMany(self::class, 'course_compulsory', 'course_id', 'compulsory_course_id')
            ->withTimestamps();
    }

    public function compulsoryFor()
    {
        return $this->belongsToMany(self::class, 'course_compulsory', 'compulsory_course_id', 'course_id')
            ->withTimestamps();
    }

    public function cohorts()
    {
        return $this->belongsToMany(Cohort::class,'course_cohort','course_id','cohort_id')
            ->withTimestamps();
    }

    public function enrolments()
    {
        return $this->hasMany(Enrolment::class);
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    public function announcements()
    {
        return $this->hasMany(CourseAnnouncement::class);
    }

    public function timeSlots()
    {
        return $this->hasMany(CourseTimeSlot::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class);
    }

    public function getThumbnailUrlAttribute(): string
    {
        $path = $this->thumbnail_path;
        if (is_string($path) && preg_match('#^https?://#i', $path) && filter_var($path, FILTER_VALIDATE_URL)) return $path;
        if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        return asset('images/course-placeholder.svg');
    }

    public function briefDescription(): string
    {
        $text = html_entity_decode(strip_tags((string) ($this->summary ?: $this->description)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $text)), 240);
    }

    public function publicData(): array
    {
        return ['id'=>$this->id,'title'=>$this->title,'code'=>$this->code,'summary'=>$this->briefDescription(),
            'thumbnail_url'=>$this->thumbnail_url,'delivery_mode'=>$this->delivery_mode,
            'start_date'=>$this->start_date,'end_date'=>$this->end_date,
            'branches'=>$this->branches->map(fn ($branch) => $branch->only(['id','name','code']))];
    }
}
