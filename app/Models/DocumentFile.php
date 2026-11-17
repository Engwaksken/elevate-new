<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentFile extends Model
{
    use SoftDeletes;

    protected $fillable = ['department_id', 'folder_id', 'name', 'extension', 'mime_type', 'size', 'path', 'version', 'created_by', 'updated_by', 'deleted_by', 'delete_batch', 'is_delete_root'];
    protected $casts = ['size' => 'integer', 'version' => 'integer'];

    /** Types the browser may show inline; everything else is downloaded. */
    public const INLINE_TYPES = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'txt'];

    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function folder(): BelongsTo { return $this->belongsTo(DocumentFolder::class, 'folder_id'); }
    public function deleter(): BelongsTo { return $this->belongsTo(User::class, 'deleted_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function editor(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function versions(): HasMany { return $this->hasMany(DocumentFileVersion::class)->orderByDesc('version'); }

    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class, 'shareable_id')->where('shareable_type', DocumentShare::FILE);
    }

    public function canOpenInline(): bool
    {
        return in_array(strtolower((string) $this->extension), self::INLINE_TYPES, true);
    }

    /** [Font Awesome icon, colour class] for the file type, like the Office icons. */
    public function iconInfo(): array
    {
        return self::iconFor((string) $this->extension);
    }

    public static function iconFor(string $extension): array
    {
        return match (strtolower($extension)) {
            'doc', 'docx', 'odt', 'rtf' => ['fa-file-word', 'word'],
            'xls', 'xlsx', 'csv', 'ods' => ['fa-file-excel', 'excel'],
            'ppt', 'pptx', 'odp' => ['fa-file-powerpoint', 'powerpoint'],
            'pdf' => ['fa-file-pdf', 'pdf'],
            'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp' => ['fa-file-image', 'image'],
            'zip', 'rar', '7z', 'gz', 'tar' => ['fa-file-zipper', 'archive'],
            'mp4', 'mov', 'avi', 'mkv', 'webm' => ['fa-file-video', 'video'],
            'mp3', 'wav', 'm4a', 'ogg' => ['fa-file-audio', 'audio'],
            'txt', 'md' => ['fa-file-lines', 'text'],
            default => ['fa-file', 'generic'],
        };
    }

    public function humanSize(): string
    {
        return self::formatBytes($this->size);
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes.' B';
        $units = ['KB', 'MB', 'GB'];
        $value = $bytes / 1024;
        $i = 0;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return rtrim(rtrim(number_format($value, 1), '0'), '.').' '.$units[$i];
    }
}
