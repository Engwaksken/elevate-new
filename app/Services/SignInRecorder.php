<?php

namespace App\Services;

use App\Models\LoginAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Records a successful sign-in: updates the user's last_login_at and keeps one
 * row per sign-in in login_audits, so admins can see every sign-in over time.
 */
class SignInRecorder
{
    public function record(User $user, Request $request, string $channel = 'web', array $extra = []): void
    {
        $user->forceFill(['last_login_at' => now()] + $extra)->save();

        // The history row must never stop someone signing in.
        try {
            LoginAudit::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'successful' => true,
                'channel' => $channel,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                'attempted_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
