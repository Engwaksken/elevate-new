<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class IndexSupportTicketQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', \App\Models\ItSupportTicket::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'in:open,in_progress,awaiting_requester,resolved'],
            'priority' => ['sometimes', 'in:low,normal,high,urgent'],
            'category' => ['sometimes', \Illuminate\Validation\Rule::in(array_keys(\App\Models\ItSupportTicket::CATEGORIES))],
            'assignee_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
