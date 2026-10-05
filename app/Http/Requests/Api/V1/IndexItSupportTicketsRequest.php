<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IndexItSupportTicketsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'in:open,in_progress,awaiting_requester,resolved'],
            'priority' => ['sometimes', 'in:low,normal,high,urgent'],
            'category' => ['sometimes', 'in:general,access,learning,procurement,other'],
            'assignee_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
