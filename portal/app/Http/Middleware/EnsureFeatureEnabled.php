<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers 404 for routes that belong to a switched-off module, so a hidden
 * feature cannot be reached by typing its URL. Usage: ->middleware('feature:external_grants')
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless(config("gmu.{$feature}"), 404);

        return $next($request);
    }
}
