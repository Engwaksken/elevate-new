<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResumeUpload extends Model
{
    protected $fillable = [
        'user_id','resume_id','original_name','stored_name','mime_type',
        'file_size','path','status','extracted_text','parsed_data',
        'parsing_error','processed_at',
    ];

    protected $casts = [
        'parsed_data'=>'array',
        'processed_at'=>'datetime',
    ];

    public function user(){ return $this->belongsTo(User::class); }
    public function resume(){ return $this->belongsTo(Resume::class); }
}
