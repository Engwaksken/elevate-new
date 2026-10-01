<?php

namespace App\Http\Controllers\Api\V1\Participant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
            'device_name' => ['nullable','string','max:120'],
        ]);

        $user = User::where('email', strtolower(trim($credentials['email'])))->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        if (! $user->isParticipant() || ! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['This account is not authorised for the participant mobile app.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken(
            $credentials['device_name'] ?? 'ElevateHer360 Participant App',
            ['participant']
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'participant_code' => $user->participant_code,
                'phone' => $user->phone,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Signed out successfully.']);
    }
}
