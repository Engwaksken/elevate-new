<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    // Choices for the education level dropdowns; anything else is entered via "Other".
    public const EDUCATION_LEVELS = [
        'No formal education', 'Primary', 'Secondary (O-Level)', 'Secondary (A-Level)',
        'Certificate', 'Diploma', "Bachelor's degree", "Master's degree", 'Doctorate (PhD)',
    ];

    protected $fillable = [
        'user_id','branch_id','surname','given_name','other_name','gender',
        'date_of_birth','country','district','location','is_pwd',
        'disability_types','disability_other','education_level',
        'employment_status','career_interests','preferred_language','metadata',
        'photo_path',
    ];

    // Internal private-disk path; clients use the authenticated photo URL from GET /profile.
    protected $hidden = ['photo_path'];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_pwd' => 'boolean',
        'disability_types' => 'array',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
}
