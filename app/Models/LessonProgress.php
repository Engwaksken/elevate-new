<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    protected $fillable = [
        'lesson_id','user_id','first_opened_at','last_opened_at',
        'completed_at','time_spent_seconds'
    ];

    protected $casts = [
        'first_opened_at' => 'datetime',
        'last_opened_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
