<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseFeedbackResponse extends Model
{
    protected $fillable = [
        'course_id', 'enrolment_id', 'user_id', 'overall_rating', 'content_rating',
        'instructor_rating', 'recommend_rating', 'key_learning', 'what_to_improve',
        'additional_comments', 'submitted_at',
    ];

    protected $casts = ['submitted_at' => 'datetime'];

    public function course() { return $this->belongsTo(Course::class); }
    public function user() { return $this->belongsTo(User::class); }
}
