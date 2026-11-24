<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseAnnouncement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_id','cohort_id','title','body','published_at','expires_at','created_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /** Set when the announcement is for one cohort only; null means the whole course. */
    public function cohort()
    {
        return $this->belongsTo(Cohort::class);
    }

    /** Announcements a participant in the given cohort should see. */
    public function scopeVisibleToCohort($query, ?int $cohortId)
    {
        return $query->where(fn ($q) => $q->whereNull('course_announcements.cohort_id')
            ->when($cohortId, fn ($q) => $q->orWhere('course_announcements.cohort_id', $cohortId)));
    }

    /**
     * Announcements for a participant's enrolments: each course's whole-course
     * announcements plus those for the cohort they are in on that course.
     */
    public function scopeForEnrolments($query, iterable $enrolments)
    {
        $enrolments = collect($enrolments);

        if ($enrolments->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($enrolments) {
            foreach ($enrolments as $enrolment) {
                $q->orWhere(fn ($q) => $q->where('course_announcements.course_id', $enrolment->course_id)
                    ->visibleToCohort($enrolment->cohort_id ? (int) $enrolment->cohort_id : null));
            }
        });
    }

    /** Published and not yet expired. */
    public function scopeCurrent($query)
    {
        return $query->where(fn ($x) => $x->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($x) => $x->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
