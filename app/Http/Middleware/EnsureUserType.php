<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to the given public-facing user types
 * (participant, mentor, employer). A user also qualifies when they
 * hold a role of the same name, matching PartnerAuthController's check.
 *
 * Usage: ->middleware('user_type:mentor,participant')
 */
class EnsureUserType
{
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();

        abort_unless($user && $user->isActive(), 403, 'Your account is not active.');

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        abort_unless(
            in_array($user->user_type, $types, true)
                || $user->hasAnyRole($types, array_map('ucfirst', $types)),
            403,
            'This area is not available for your account type.'
        );

        return $next($request);
    }
}
