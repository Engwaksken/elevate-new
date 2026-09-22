# Phase 9 model patches

Add to Activity:

```php
public function deliverables()
{
    return $this->hasMany(\App\Models\Deliverable::class);
}
```

Add to StaffExit:
```php
public function employee()
{
    return $this->belongsTo(\App\Models\Employee::class);
}
```

Recommended next improvements:
- public holiday table and exclusion from leave calculations
- leave carry-forward rules
- leave attachments
- employee document upload UI
- appraisal self-assessment and manager-review screens
- exit interview UI
- calendar sync for approved leave and appraisal deadlines
- notification reminders
