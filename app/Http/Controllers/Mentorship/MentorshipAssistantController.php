<?php

namespace App\Http\Controllers\Mentorship;

use App\Http\Controllers\Controller;
use App\Services\MentorshipAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MentorshipAssistantController extends Controller
{
    public function message(Request $request, MentorshipAssistantService $assistant): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1500'],
            'history' => ['nullable', 'array', 'max:12'],
        ]);

        $reply = $assistant->reply($request->user(), $data['message'], $data['history'] ?? []);

        return response()->json($reply);
    }
}
