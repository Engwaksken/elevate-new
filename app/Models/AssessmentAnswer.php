<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentAnswer extends Model
{
    protected $fillable = [
        'assessment_attempt_id','assessment_question_id','answer_text',
        'answer_json','awarded_marks','grader_feedback'
    ];

    protected $casts = [
        'answer_json' => 'array',
        'awarded_marks' => 'decimal:2',
    ];
}
