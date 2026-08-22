<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Audit finding H4 — a password chosen by somebody else is a shared secret.
 *
 * Accounts created or reset by the Coordinator, and the seeded administrator,
 * are held at the profile page until the user sets their own password.
 */
class ForcePasswordChange
{
    /** Routes the user still needs while they are being held. */
    private const ALLOWED = [
        'profile.edit',
        'password.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password && ! $request->routeIs(self::ALLOWED)) {
            return redirect()->route('profile.edit')
                ->with('error', 'Please choose your own password before continuing.');
        }

        return $next($request);
    }
}
