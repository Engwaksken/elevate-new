<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningFile extends Model
{
    protected $fillable = [
        'course_id','lesson_id','original_name','stored_name','disk','path',
        'mime_type','size_bytes','uploaded_by'
    ];
}
