<?php

namespace App\Http\Controllers\Api\V1\Participant;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Services\AccountDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * GET  /account/deletion-request  -> latest request (or null)
 * POST /account/deletion-request  {password, reason?} -> creates a pending request for admin review
 */
class AccountDeletionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $latest = AccountDeletionRequest::where('user_id', $request->user()->id)->latest('id')->first();

        return response()->json(['request' => $latest?->toApiArray(), 'days_to_process' => AccountDeletionRequest::DAYS_TO_PROCESS]);
    }

    public function store(Request $request, AccountDeletionService $deletions): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! Hash::check($data['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'That password is not correct.']);
        }

        $deletionRequest = $deletions->request($request->user(), $data['reason'] ?? null, 'app');

        return response()->json([
            'message' => 'Your request has been sent. Your account will be deleted within '.AccountDeletionRequest::DAYS_TO_PROCESS.' days and you will be signed out.',
            'request' => $deletionRequest->toApiArray(),
        ], 201);
    }
}
