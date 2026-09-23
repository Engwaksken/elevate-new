<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoverLetter extends Model
{
    protected $fillable = [
        'user_id','resume_id','job_id','title','employer_name','job_title',
        'recipient_name','body','source','ai_generated',
    ];

    protected $casts = ['ai_generated'=>'boolean'];

    public function user(){ return $this->belongsTo(User::class); }
    public function resume(){ return $this->belongsTo(Resume::class); }
    public function job(){ return $this->belongsTo(Job::class); }
    public function uploads(){ return $this->hasMany(CoverLetterUpload::class); }
}
