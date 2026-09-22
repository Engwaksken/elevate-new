<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    protected $fillable = [
        'course_id','cohort_id','title','session_date','starts_at','ends_at','venue'
    ];

    protected $casts = ['session_date' => 'date'];
}
