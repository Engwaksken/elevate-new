# Phase 7 model patches

Add to `User`:

```php
public function resumes()
{
    return $this->hasMany(\App\Models\Resume::class);
}
```

Add to `ResumeExperience`, `ResumeEducation`, `ResumeSkill`,
`ResumeCertification`, `ResumeLanguage`, and `ResumeProject` if needed:

```php
public function resume()
{
    return $this->belongsTo(\App\Models\Resume::class);
}
```

Important Phase 6 migration dependency:
Phase 6 referenced `resumes` from `job_applications.resume_id`.
If Phase 6 migration has not yet been run, run the Resume migration before
adding the foreign key, or adjust Phase 6 so the foreign key is added after
the Resume tables exist.

Recommended clean ordering in a fresh project:
1. Phase 1–5
2. Phase 7 Resume tables
3. Phase 6 Jobs tables
4. Remaining Phase 7 library/job enhancements

For an existing project where Phase 6 was already applied without `resume_id`
FK, add it in a small follow-up migration after `resumes` exists.
