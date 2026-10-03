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
        'layout',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'layout' => 'array',
    ];

    /**
     * The print fields an admin can place on a certificate and their defaults.
     */
    public static function fieldDefinitions(): array
    {
        return [
            'heading' => ['label' => 'Certificate heading', 'sample' => 'Certificate of Completion'],
            'name' => ['label' => 'Participant name', 'sample' => 'Jane Achieng'],
            'course' => ['label' => 'Course / event title', 'sample' => 'Digital Marketing'],
            'certificate_number' => ['label' => 'Certificate number', 'sample' => 'EH-CERT-2026-0001'],
            'participant_id' => ['label' => 'Participant ID', 'sample' => 'EH-P-000123'],
            'start_period' => ['label' => 'Start period', 'sample' => 'Start: 01 Jan 2026'],
            'end_period' => ['label' => 'End period', 'sample' => 'End: 30 Jun 2026'],
            'issued' => ['label' => 'Date issued', 'sample' => 'Issued: 30 Jun 2026'],
        ];
    }

    public static function fontOptions(): array
    {
        return [
            'DejaVu Sans' => 'DejaVu Sans (default)',
            'DejaVu Serif' => 'DejaVu Serif',
            'Helvetica' => 'Helvetica / Arial',
            'Times-Roman' => 'Times New Roman',
            'Courier' => 'Courier',
        ];
    }

    /**
     * A complete layout: every defined field merged with its stored config and defaults.
     */
    public function fieldLayout(): array
    {
        $stored = $this->layout ?: [];
        $layout = [];

        foreach (static::defaultLayout() as $key => $default) {
            $layout[$key] = array_merge($default, is_array($stored[$key] ?? null) ? $stored[$key] : []);
        }

        return $layout;
    }

    public static function defaultLayout(): array
    {
        $base = [
            'enabled' => true,
            'x' => 50,
            'y' => 50,
            'width' => 80,
            'align' => 'center',
            'font_family' => 'DejaVu Sans',
            'font_size' => 18,
            'color' => '#2b2b2b',
            'bold' => false,
            'italic' => false,
            'underline' => false,
            'uppercase' => false,
            'letter_spacing' => 0,
            'prefix' => '',
            'suffix' => '',
        ];

        return [
            'heading' => array_merge($base, ['y' => 22, 'font_size' => 34, 'color' => '#800000', 'bold' => true]),
            'name' => array_merge($base, ['y' => 40, 'font_size' => 32, 'bold' => true]),
            'course' => array_merge($base, ['y' => 52, 'font_size' => 24]),
            'certificate_number' => array_merge($base, ['x' => 25, 'y' => 86, 'width' => 40, 'align' => 'left', 'font_size' => 13]),
            'participant_id' => array_merge($base, ['x' => 75, 'y' => 86, 'width' => 40, 'align' => 'right', 'font_size' => 13]),
            'start_period' => array_merge($base, ['x' => 25, 'y' => 91, 'width' => 40, 'align' => 'left', 'font_size' => 13]),
            'end_period' => array_merge($base, ['x' => 75, 'y' => 91, 'width' => 40, 'align' => 'right', 'font_size' => 13]),
            'issued' => array_merge($base, ['enabled' => false, 'x' => 50, 'y' => 95, 'font_size' => 13]),
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function courses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'certificate_template_course');
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

        if ($courseId && $template = (clone $active)->where('context_type', 'course')
            ->where(fn ($query) => $query->where('course_id', $courseId)
                ->orWhereHas('courses', fn ($courses) => $courses->where('courses.id', $courseId)))->first()) {
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
