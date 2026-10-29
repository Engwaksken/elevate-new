<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Services\AccountDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Public page explaining how to delete an ElevateHer360 account (linked from Google Play),
 * with a request form for signed-in users.
 */
class AccountDeletionController extends Controller
{
    public function show(Request $request)
    {
        return view('legal.account-deletion', [
            'pending' => $request->user()
                ? AccountDeletionRequest::where('user_id', $request->user()->id)->where('status', AccountDeletionRequest::PENDING)->latest('id')->first()
                : null,
        ]);
    }

    public function store(Request $request, AccountDeletionService $deletions)
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Please tick the box to confirm you understand.']);

        if (! Hash::check($data['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'That password is not correct.']);
        }

        $deletions->request($request->user(), $data['reason'] ?? null, 'web');

        return redirect()->route('account-deletion.show')
            ->with('success', 'Your request has been sent. Your account will be deleted within '.AccountDeletionRequest::DAYS_TO_PROCESS.' days.');
    }
}
