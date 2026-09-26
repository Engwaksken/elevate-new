<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParticipantUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 403);
        abort_unless(
            method_exists($user, 'isActive') ? $user->isActive() : $user->status === 'active',
            403,
            'Your account is not active.'
        );

        abort_unless(
            method_exists($user, 'isParticipant')
                ? $user->isParticipant()
                : $user->user_type === 'participant',
            403,
            'Participant access only.'
        );

        return $next($request);
    }
}
