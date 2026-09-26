<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentAttempt extends Model
{
    protected $fillable=[
        'assessment_id','user_id','attempt_number','score','percentage',
        'status','started_at','submitted_at','graded_at','graded_by'
    ];

    protected $casts=[
        'started_at'=>'datetime',
        'submitted_at'=>'datetime',
        'graded_at'=>'datetime',
        'score'=>'decimal:2',
        'percentage'=>'decimal:2',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
