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
            'status' => ['sometimes', 'nullable', 'in:open,in_progress,awaiting_requester,resolved'],
            'priority' => ['sometimes', 'nullable', 'in:low,normal,high,urgent'],
            'category' => ['sometimes', 'nullable', \Illuminate\Validation\Rule::in(array_keys(\App\Models\ItSupportTicket::CATEGORIES))],
            'assignee_id' => ['sometimes', 'nullable', 'regex:/^(none|[1-9][0-9]*)$/'],
            'search' => ['sometimes', 'nullable', 'string', 'max:120'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
