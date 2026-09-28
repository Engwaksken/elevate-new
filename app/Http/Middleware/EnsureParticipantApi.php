<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParticipantApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user
            && $user->isParticipant()
            && $user->isActive(),
            403,
            'This endpoint is available to active participant accounts only.'
        );

        return $next($request);
    }
}
