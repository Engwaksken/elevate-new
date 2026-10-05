<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyItQueueReview extends Model
{
    use HasFactory;

    protected $table = 'daily_it_queue_reviews';

    protected $fillable = [
        'run_date',
    ];

    protected $casts = [
        'run_date' => 'date',
    ];
}
