<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantMessageAttachment extends Model
{
    /** Raster images are previewed inline; everything else downloads. SVG is deliberately excluded. */
    public const INLINE_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    protected $fillable = ['message_id', 'path', 'original_name', 'mime_type', 'size'];

    protected $casts = ['size' => 'integer'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(ParticipantMessage::class, 'message_id');
    }

    public function isImage(): bool
    {
        return in_array($this->mime_type, self::INLINE_IMAGE_TYPES, true);
    }

    public function sizeLabel(): string
    {
        $size = (int) $this->size;

        return match (true) {
            $size >= 1048576 => number_format($size / 1048576, 1).' MB',
            $size >= 1024 => number_format($size / 1024, 0).' KB',
            default => $size.' B',
        };
    }
}
