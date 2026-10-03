<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
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
        if ($this->boolean('terms') && ! $this->filled('privacy_policy')) {
            $this->merge(['privacy_policy' => 1]);
        }
    }

    public function rules(): array
    {
        return [
            'surname' => ['required', 'string', 'max:100'],
            'given_name' => ['required', 'string', 'max:100'],
            'other_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email:rfc,dns', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'min:7', 'max:30'],
            'gender' => ['nullable', 'in:female,male,other,prefer_not_to_say'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'country' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:190'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'is_pwd' => ['nullable', 'boolean'],
            'education_level' => ['nullable', 'string', 'max:150'],
            'employment_status' => ['nullable', 'string', 'max:150'],
            'career_interests' => ['nullable', 'string', 'max:2000'],
            'interests' => ['nullable', 'array', 'max:3'],
            'interests.*' => ['nullable', 'string', 'in:learning,mentorship,jobs'],
            'preferred_language' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'privacy_policy' => ['accepted'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'interests.*.in' => 'Choose Learning, Mentorship or Jobs (or All).',
        ];
    }
}
