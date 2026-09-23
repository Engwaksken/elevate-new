<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoverLetterUpload extends Model
{
    protected $fillable = [
        'user_id','cover_letter_id','original_name','stored_name','mime_type',
        'file_size','path','status','extracted_text','parsed_data',
        'parsing_error','processed_at',
    ];

    protected $casts = [
        'parsed_data' => 'array',
        'processed_at' => 'datetime',
    ];

    public function coverLetter()
    {
        return $this->belongsTo(CoverLetter::class);
    }
}
