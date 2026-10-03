<?php

namespace App\Http\Controllers\Api\V1\Participant;

use App\Http\Controllers\Controller;
use App\Services\MentorshipAssistantService;
use Illuminate\Http\Request;

/**
 * AI career mentor for participants: POST /mentorship/assistant
 */
class MentorshipAssistantController extends Controller
{
    public function message(Request $request, MentorshipAssistantService $assistant)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1500'],
            'history' => ['nullable', 'array', 'max:12'],
        ]);

        $reply = $assistant->reply($request->user(), $data['message'], $data['history'] ?? []);

        return response()->json($reply);
    }
}
