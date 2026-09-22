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

    public function rules(): array
    {
        return [
            'surname' => ['required', 'string', 'max:100'],
            'given_name' => ['required', 'string', 'max:100'],
            'other_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email:rfc,dns', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
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
            'preferred_language' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'privacy_policy' => ['accepted'],
            'terms' => ['accepted'],
        ];
    }
}
