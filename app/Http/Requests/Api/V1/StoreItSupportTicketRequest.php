<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreItSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('create', \App\Models\ItSupportTicket::class) ?? false)
            && in_array($this->user()?->user_type, ['participant', 'staff', 'instructor', 'mentor', 'employer'], true);
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['sometimes', 'in:general,access,learning,procurement,other'],
            'priority' => ['sometimes', 'in:low,normal,high,urgent'],
        ];
    }
}
