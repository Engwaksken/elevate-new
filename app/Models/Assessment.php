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
        'submission_formats',
        'max_submission_files',
    ];

    /**
     * File formats an instructor can allow for assignment submissions.
     * "extensions" are what participants see and pick; "aliases" are extra
     * extensions the server may detect from the same content (e.g. an MP3
     * is detected as "mpga"), accepted only for validation.
     */
    public const SUBMISSION_FORMATS = [
        'document' => ['label' => 'Documents', 'hint' => 'PDF, Word, text', 'extensions' => ['pdf', 'doc', 'docx', 'odt', 'rtf', 'txt'], 'aliases' => []],
        'spreadsheet' => ['label' => 'Spreadsheets', 'hint' => 'Excel, CSV', 'extensions' => ['xls', 'xlsx', 'ods', 'csv'], 'aliases' => []],
        'presentation' => ['label' => 'Presentations', 'hint' => 'PowerPoint', 'extensions' => ['ppt', 'pptx', 'odp'], 'aliases' => []],
        'image' => ['label' => 'Images', 'hint' => 'JPG, PNG, WebP', 'extensions' => ['jpg', 'jpeg', 'png', 'webp'], 'aliases' => []],
        'video' => ['label' => 'Video', 'hint' => 'MP4, MOV, WebM, 3GP', 'extensions' => ['mp4', 'mov', 'webm', '3gp', 'm4v'], 'aliases' => ['qt', 'mp4v', 'mpg4', '3gpp']],
        'audio' => ['label' => 'Audio', 'hint' => 'MP3, M4A, WAV, AAC, OGG', 'extensions' => ['mp3', 'm4a', 'wav', 'aac', 'ogg', 'amr'], 'aliases' => ['mpga', 'mp2', 'mp4a', 'oga', 'weba']],
        'archive' => ['label' => 'ZIP archives', 'hint' => 'ZIP', 'extensions' => ['zip'], 'aliases' => []],
    ];

    /** Formats accepted for assignments created before formats could be chosen. */
    public const DEFAULT_SUBMISSION_FORMATS = ['document', 'spreadsheet', 'presentation', 'image', 'archive'];

    /** Upper limit an instructor can set for files per submission. */
    public const MAX_SUBMISSION_FILES_LIMIT = 20;

    protected $casts = [
        'opens_at' => 'datetime',
        'due_at' => 'datetime',
        'duration_minutes' => 'integer',
        'total_marks' => 'decimal:2',
        'is_published' => 'boolean',
        'submission_formats' => 'array',
        'max_submission_files' => 'integer',
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

    /** Assignment brief/material files (many per assessment). */
    public function files()
    {
        return $this->hasMany(LearningFile::class)->orderBy('id');
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

    /** Assignments must include at least one file; quizzes and exams are answered on the form. */
    public function requiresAttachment(): bool
    {
        return $this->type === 'assignment';
    }

    /** @return array<int,string> Allowed format keys (see SUBMISSION_FORMATS). */
    public function allowedSubmissionFormats(): array
    {
        $formats = array_values(array_intersect(array_keys(self::SUBMISSION_FORMATS), (array) ($this->submission_formats ?? [])));

        return $formats ?: self::DEFAULT_SUBMISSION_FORMATS;
    }

    /** @return array<int,string> Extensions participants may submit, e.g. ["pdf","docx"]. */
    public function submissionExtensions(): array
    {
        return collect($this->allowedSubmissionFormats())
            ->flatMap(fn ($format) => self::SUBMISSION_FORMATS[$format]['extensions'])
            ->unique()->values()->all();
    }

    /** Value for the "mimes" validation rule (extensions plus detected aliases). */
    public function submissionMimesRule(): string
    {
        return collect($this->allowedSubmissionFormats())
            ->flatMap(fn ($format) => array_merge(self::SUBMISSION_FORMATS[$format]['extensions'], self::SUBMISSION_FORMATS[$format]['aliases']))
            ->unique()->implode(',');
    }

    /** Human list such as "Documents, Images". */
    public function submissionFormatLabels(): string
    {
        return collect($this->allowedSubmissionFormats())
            ->map(fn ($format) => self::SUBMISSION_FORMATS[$format]['label'])
            ->implode(', ');
    }

    public function maxSubmissionFiles(): int
    {
        return min(self::MAX_SUBMISSION_FILES_LIMIT, max(1, (int) ($this->max_submission_files ?? 1)));
    }
}
