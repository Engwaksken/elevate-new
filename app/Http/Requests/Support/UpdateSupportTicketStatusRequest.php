<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupportTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateStatus', $this->route('ticket')) ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', 'in:open,in_progress,awaiting_requester,resolved']];
    }
}
