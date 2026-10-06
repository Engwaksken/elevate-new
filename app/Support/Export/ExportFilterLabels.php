<?php

namespace App\Support\Export;

use App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

/**
 * Turns raw query-string filters into readable labels for export headers,
 * e.g. "course_id=5" becomes "Course: Web Development".
 * Only numeric values for known keys are looked up; anything else
 * (search text, statuses, string modules) passes through unchanged.
 */
class ExportFilterLabels
{
    /** query key => model class (keys are checked with and without an "_id" suffix) */
    private const MODELS = [
        'course' => Models\Course::class,
        'approved_course' => Models\Course::class,
        'cohort' => Models\Cohort::class,
        'branch' => Models\Branch::class,
        'programme' => Models\Programme::class,
        'project' => Models\Project::class,
        'event' => Models\Event::class,
        'module' => Models\CourseModule::class,
        'course_module' => Models\CourseModule::class,
        'lesson' => Models\Lesson::class,
        'assessment' => Models\Assessment::class,
        'survey' => Models\Survey::class,
        'course_call' => Models\CourseCall::class,
        'user' => Models\User::class,
        'assignee' => Models\User::class,
        'member' => Models\User::class,
        'mentor' => Models\User::class,
        'mentee' => Models\User::class,
        'manager_user' => Models\User::class,
        'supervisor_user' => Models\User::class,
        'requester' => Models\User::class,
        'owner_user' => Models\User::class,
        'employee' => Models\Employee::class,
        'employer' => Models\Employer::class,
        'job' => Models\Job::class,
        'supplier' => Models\Supplier::class,
        'department' => Models\Department::class,
        'indicator' => Models\Indicator::class,
        'workplan' => Models\Workplan::class,
        'activity' => Models\Activity::class,
        'library_category' => Models\LibraryCategory::class,
        'hr_kpi_template' => Models\HrKpiTemplate::class,
        'contract' => Models\EmploymentContract::class,
    ];

    private const DISPLAY = ['name', 'title', 'company_name', 'full_name', 'label', 'code'];

    /** Friendly label for a query key: "assignee_id" => "Assignee". */
    public static function label(string $key): string
    {
        return Str::headline(preg_replace('/_(user_)?id$|_user$/', '', $key) ?: $key);
    }

    /** Readable value for a filter; unknown keys and non-numeric values pass through. */
    public static function value(string $key, mixed $value): mixed
    {
        $base = preg_replace('/_id$/', '', $key);
        $class = self::MODELS[$key] ?? self::MODELS[$base] ?? null;

        if (! $class) {
            return $value;
        }

        if (is_array($value)) {
            return array_map(fn ($v) => self::value($key, $v), $value);
        }

        if (! is_numeric($value)) {
            return $value;
        }

        try {
            $model = $class::query()->find((int) $value);
        } catch (Throwable) {
            return $value;
        }

        return $model ? self::display($model) : $value;
    }

    private static function display(Model $model): string
    {
        if ($model instanceof Models\Employee) {
            return $model->user?->name ?? $model->employee_number ?? '#'.$model->getKey();
        }

        if ($model instanceof Models\EmploymentContract) {
            return trim(Str::headline((string) $model->contract_type).' '.optional($model->start_date)->format('Y-m-d').' – '.optional($model->end_date)->format('Y-m-d'), ' –');
        }

        foreach (self::DISPLAY as $attribute) {
            $text = $model->getAttribute($attribute);
            if (is_scalar($text) && trim((string) $text) !== '') {
                return (string) $text;
            }
        }

        return '#'.$model->getKey();
    }
}
