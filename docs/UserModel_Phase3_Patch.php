<?php
// Merge these casts/relationships into App\Models\User

protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'mfa_enabled' => 'boolean',
        'mfa_secret' => 'encrypted',
        'mfa_recovery_codes' => 'encrypted',
        'mfa_confirmed_at' => 'datetime',
    ];
}

public function enrolments()
{
    return $this->hasMany(\App\Models\Enrolment::class);
}

public function instructedCourses()
{
    return $this->belongsToMany(\App\Models\Course::class,'course_instructors')
        ->withPivot('is_lead')->withTimestamps();
}
