<?php

namespace App\Http\Requests\Staff;

use App\Models\ItSupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ItSupportTicket::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category' => ['required', Rule::in(array_keys(ItSupportTicket::CATEGORIES))],
            'priority' => ['required', Rule::in(array_keys(ItSupportTicket::PRIORITIES))],
        ];
    }
}
