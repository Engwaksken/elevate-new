<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CertificateTemplate extends Model
{
    protected $fillable = [
        'name',
        'context_type',
        'course_id',
        'event_id',
        'background_path',
        'orientation',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The template to print on: the newest active one for the course or event, else the default.
     */
    public static function resolveFor(?int $courseId = null, ?int $eventId = null): ?self
    {
        $active = static::query()->where('is_active', true)->latest('id');

        if ($eventId && $template = (clone $active)->where('context_type', 'event')->where('event_id', $eventId)->first()) {
            return $template;
        }

        if ($courseId && $template = (clone $active)->where('context_type', 'course')->where('course_id', $courseId)->first()) {
            return $template;
        }

        return (clone $active)->where('context_type', 'default')->first();
    }

    /**
     * Absolute path of the background image for DOMPDF, or null when the file is missing.
     */
    public function backgroundFilePath(): ?string
    {
        if (! $this->background_path || ! Storage::disk('public')->exists($this->background_path)) {
            return null;
        }

        return Storage::disk('public')->path($this->background_path);
    }
}
