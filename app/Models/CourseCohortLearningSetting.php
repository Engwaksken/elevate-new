<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseCohortLearningSetting extends Model
{
    protected $fillable=['course_id','cohort_id','sequential_modules','instructor_release_required'];
    protected $casts=['sequential_modules'=>'boolean','instructor_release_required'=>'boolean'];
}
