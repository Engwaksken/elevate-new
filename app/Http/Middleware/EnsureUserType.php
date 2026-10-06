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
 * The virtual type "mentee" admits anyone who can be mentored
 * (participants and staff instructors / trainers).
 *
 * Usage: ->middleware('user_type:mentor,participant')
 *        ->middleware('user_type:mentee,mentor')
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

        // "mentee" is a virtual type: participants plus staff instructors /
        // trainers, who may also need mentors (see User::canBeMentee()).
        if (in_array('mentee', $types, true) && $user->canBeMentee()) {
            return $next($request);
        }

        $types = array_values(array_diff($types, ['mentee']));

        abort_unless(
            $types !== [] && (in_array($user->user_type, $types, true)
                || $user->hasAnyRole($types, array_map('ucfirst', $types))),
            403,
            'This area is not available for your account type.'
        );

        return $next($request);
    }
}
