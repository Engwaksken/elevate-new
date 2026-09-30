<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Assessment extends Model
{
    protected $fillable = [
        'course_id',
        'course_module_id',
        'title',
        'type',
        'instructions',
        'pass_mark',
        'max_attempts',
        'opens_at',
        'due_at',
        'duration_minutes',
        'total_marks',
        'attachment_path',
        'is_published',
    ];

    protected $casts = [
        'opens_at' => 'datetime',
        'due_at' => 'datetime',
        'duration_minutes' => 'integer',
        'total_marks' => 'decimal:2',
        'is_published' => 'boolean',
    ];

    protected $appends = [
        'attachment_url',
    ];

    public function questions()
    {
        return $this->hasMany(AssessmentQuestion::class)->orderBy('position');
    }

    public function attempts()
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function extensionRequests()
    {
        return $this->hasMany(AssignmentExtensionRequest::class);
    }

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return Storage::disk('public')->url($this->attachment_path);
    }
}
