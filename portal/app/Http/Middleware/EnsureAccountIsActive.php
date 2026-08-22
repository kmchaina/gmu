<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Audit finding H1 — deactivation used to be cosmetic.
 *
 * The is_active check lived only in RoleMiddleware, which guards four route
 * groups, so a deactivated account kept working access to everything else.
 * This runs on every authenticated request and ends the session the moment an
 * account is deactivated, rather than at next login.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This account has been deactivated. Please contact the GMU Coordinator.',
            ]);
        }

        return $next($request);
    }
}
