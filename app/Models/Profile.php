<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    protected $fillable = [
        'user_id','branch_id','surname','given_name','other_name','gender',
        'date_of_birth','country','district','location','is_pwd',
        'disability_types','disability_other','education_level',
        'employment_status','career_interests','preferred_language','metadata',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_pwd' => 'boolean',
        'disability_types' => 'array',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
}
