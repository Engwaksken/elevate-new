<?php
namespace App\Models;

use App\Models\Concerns\StoredFile;
use Illuminate\Database\Eloquent\Model;

/**
 * A course material file: attached to a lesson (lesson_id) or to an
 * assessment/assignment brief (assessment_id). One lesson or assessment can
 * have many files.
 */
class LearningFile extends Model
{
    use StoredFile;

    protected $fillable = [
        'course_id','lesson_id','assessment_id','original_name','stored_name','disk','path',
        'mime_type','size_bytes','uploaded_by','migrated_from'
    ];

    public function course(){ return $this->belongsTo(Course::class); }
    public function lesson(){ return $this->belongsTo(Lesson::class); }
    public function assessment(){ return $this->belongsTo(Assessment::class); }
}
