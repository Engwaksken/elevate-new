<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ResumeVersion extends Model {
    protected $fillable=['resume_id','version_number','source','snapshot','created_by'];
    protected $casts=['snapshot'=>'array'];
}
