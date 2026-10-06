<?php

namespace App\Http\Requests\Participant;

use App\Models\ItSupportTicket;
use Illuminate\Foundation\Http\FormRequest;

class StoreBrowserSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ItSupportTicket::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['sometimes', \Illuminate\Validation\Rule::in(array_keys(\App\Models\ItSupportTicket::CATEGORIES))],
            'priority' => ['sometimes', 'in:low,normal,high,urgent'],
        ];
    }
}
