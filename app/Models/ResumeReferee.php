<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResumeReferee extends Model
{
    protected $fillable = ['resume_id', 'name', 'job_title', 'organisation', 'email', 'phone', 'relationship', 'position'];
}
