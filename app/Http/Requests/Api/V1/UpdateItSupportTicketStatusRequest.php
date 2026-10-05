<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ItSupportTicket;
use Illuminate\Foundation\Http\FormRequest;

class UpdateItSupportTicketStatusRequest extends FormRequest
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
