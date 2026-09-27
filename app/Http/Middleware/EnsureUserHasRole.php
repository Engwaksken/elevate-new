<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && $user->isActive(), 403, 'This area is restricted.');

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        abort_unless(
            $roles !== [] && $user->hasAnyRole($roles),
            403,
            'This area is restricted to authorised staff roles.'
        );

        return $next($request);
    }
}