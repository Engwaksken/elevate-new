<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LearningReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('reports.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'programme_id' => ['nullable', 'integer', 'exists:programmes,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ];
    }
}
