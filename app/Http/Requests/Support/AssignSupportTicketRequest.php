<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class AssignSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assign', $this->route('ticket')) ?? false;
    }

    public function rules(): array
    {
        return ['assignee_id' => ['present', 'nullable', 'integer', 'exists:users,id']];
    }
}
