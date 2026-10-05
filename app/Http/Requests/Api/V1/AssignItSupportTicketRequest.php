<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ItSupportTicket;
use Illuminate\Foundation\Http\FormRequest;

class AssignItSupportTicketRequest extends FormRequest
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
