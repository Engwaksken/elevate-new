# Phase 8 model patches

Add to `Workplan`:

```php
public function approvals()
{
    return $this->hasMany(\App\Models\WorkplanApproval::class);
}
```

Create `WorkplanApproval` model if you want explicit model access.

Add to `Indicator`:
```php
public function activities()
{
    return $this->belongsToMany(\App\Models\Activity::class)->withPivot('contribution_type')->withTimestamps();
}
```

Add to `Activity` as needed:
```php
public function assignedUsers()
{
    return $this->belongsToMany(\App\Models\User::class,'activity_assignments')->withTimestamps();
}
```

Recommended follow-up:
- task/deliverable controllers and UI
- risk/issue management UI
- calendar monthly/weekly/quarterly/annual visual component
- indicator target-vs-actual charting
- evidence/document linking
