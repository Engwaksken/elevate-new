# Model patches required

Add to `Assessment`:

```php
public function course()
{
    return $this->belongsTo(\App\Models\Course::class);
}
```

Add to `Certificate`:

```php
public function user()
{
    return $this->belongsTo(\App\Models\User::class);
}

public function course()
{
    return $this->belongsTo(\App\Models\Course::class);
}
```

Ensure `User` contains:

```php
public function instructedCourses()
{
    return $this->belongsToMany(\App\Models\Course::class, 'course_instructors')
        ->withPivot('is_lead')
        ->withTimestamps();
}
```

Ensure `Course` contains:
- `modules()`
- `instructors()`
- `cohorts()`
- `enrolments()`
- `assessments()`
