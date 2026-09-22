<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentQuestion extends Model
{
    protected $fillable = [
        'assessment_id','question_type','question_text','options','correct_answer','marks','position'
    ];
    protected $casts = ['options'=>'array','correct_answer'=>'array','marks'=>'decimal:2'];
}
