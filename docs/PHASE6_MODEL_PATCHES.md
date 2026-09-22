# Required model patches

Add to `JobApplication`:

```php
public function statusHistory()
{
    return $this->hasMany(\App\Models\JobApplicationStatusHistory::class);
}
```

Create model `JobApplicationStatusHistory` if desired:

```php
class JobApplicationStatusHistory extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'job_application_id','status','notes','changed_by','changed_at'
    ];
}
```

Add to `MentorshipGoal`:

```php
public function match()
{
    return $this->belongsTo(\App\Models\MentorMatch::class,'mentor_match_id');
}
```

Add to `User` as useful:

```php
public function employerProfile()
{
    return $this->hasOne(\App\Models\Employer::class,'owner_user_id');
}

public function jobApplications()
{
    return $this->hasMany(\App\Models\JobApplication::class);
}

public function outcomes()
{
    return $this->hasMany(\App\Models\ParticipantOutcome::class);
}
```

Note:
`job_applications.resume_id` assumes the Resume module/table from the approved full scope.
If Resume tables have not yet been created, either:
1. add the Resume module before running this migration, or
2. temporarily remove the foreign key and add it in the Resume migration.
