<?php

namespace App\Models;

use App\Models\Concerns\StoredFile;
use Illuminate\Database\Eloquent\Model;

/**
 * A file a participant uploaded with an assessment attempt (submission).
 */
class AssessmentAttemptFile extends Model
{
    use StoredFile;

    protected $fillable = [
        'assessment_attempt_id', 'original_name', 'disk', 'path', 'mime_type', 'size_bytes', 'migrated_from',
    ];

    public function attempt()
    {
        return $this->belongsTo(AssessmentAttempt::class, 'assessment_attempt_id');
    }
}
