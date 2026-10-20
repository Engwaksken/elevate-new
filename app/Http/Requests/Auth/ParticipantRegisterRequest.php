<?php

namespace App\Http\Requests\Auth;

use App\Models\Branch;
use App\Models\Cohort;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ParticipantRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The form has a single combined consent checkbox (`terms`). Treat it as
     * accepting the privacy policy too, so a checked box never fails on
     * "privacy policy must be accepted".
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('country') === 'Other' && $this->filled('country_other')) {
            $this->merge(['country' => trim((string) $this->input('country_other'))]);
        }

        if ($this->input('education_level') === 'Other' && $this->filled('education_level_other')) {
            $this->merge(['education_level' => trim((string) $this->input('education_level_other'))]);
        }

        if ($this->boolean('terms') && ! $this->filled('privacy_policy')) {
            $this->merge(['privacy_policy' => 1]);
        }

        // Branch, cohort and course belong to Learning only. Drop anything
        // left in the hidden fields when Learning is not chosen.
        if (! $this->wantsLearning()) {
            $this->merge(['branch_id' => null, 'cohort_id' => null, 'course_id' => null]);
        }
    }

    private function wantsLearning(): bool
    {
        return in_array('learning', (array) $this->input('interests', []), true);
    }

    /**
     * Learning participants must pick a branch, cohort and course, but only
     * when the form actually offers at least one choice for that field.
     */
    private function requiredForLearning(\Closure $hasChoices): \Illuminate\Validation\Rules\RequiredIf
    {
        return Rule::requiredIf(fn () => $this->wantsLearning() && $hasChoices());
    }

    /**
     * A participant belongs to one branch, so the cohort and course must be
     * offered at the branch they chose.
     */
    private function atChosenBranch($query, string $message): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($query, $message) {
            if (! $query->clone()->atBranch((int) $this->input('branch_id'))->whereKey($value)->exists()) {
                $fail($message);
            }
        };
    }

    public function rules(): array
    {
        $cohorts = Cohort::where('status', 'active');
        $courses = Course::selectable();

        return [
            'surname' => ['required', 'string', 'max:100'],
            'given_name' => ['required', 'string', 'max:100'],
            'other_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email:rfc,dns', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'min:7', 'max:30'],
            'gender' => ['nullable', 'in:female'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'country' => ['nullable', 'string', 'max:100', 'not_in:Other'],
            'country_other' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:190'],
            'branch_id' => [
                $this->requiredForLearning(fn () => Branch::where('is_active', true)->exists()),
                'nullable',
                'exists:branches,id,is_active,1',
            ],
            'cohort_id' => [
                $this->requiredForLearning(fn () => $cohorts->clone()->atBranch((int) $this->input('branch_id'))->exists()),
                'nullable',
                $this->atChosenBranch($cohorts, 'Choose an active cohort at your selected branch.'),
            ],
            // Only courses offered on the form can be chosen for auto-enrolment.
            'course_id' => [
                $this->requiredForLearning(fn () => $courses->clone()->atBranch((int) $this->input('branch_id'))->exists()),
                'nullable',
                $this->atChosenBranch($courses, 'Choose a course offered at your selected branch.'),
            ],
            'is_pwd' => ['nullable', 'boolean'],
            'education_level' => ['nullable', 'string', 'max:150', 'not_in:Other'],
            'education_level_other' => ['nullable', 'string', 'max:150'],
            'employment_status' => ['nullable', 'string', 'max:150'],
            'career_interests' => ['nullable', 'string', 'max:2000'],
            'interests' => ['nullable', 'array', 'max:3'],
            'interests.*' => ['nullable', 'string', 'in:learning,mentorship,jobs'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'privacy_policy' => ['accepted'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'interests.*.in' => 'Choose Learning, Mentorship or Jobs (or All).',
            'branch_id.required' => 'Choose a branch for your learning.',
            'cohort_id.required' => 'Choose a cohort for your learning.',
            'course_id.required' => 'Choose a course for your learning.',
        ];
    }
}
