<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces a student who logged in with a dean-generated temporary password
 * (must_change_password = true) to set their own password before touching
 * anything else. Lives outside this group to avoid redirecting to itself.
 */
class EnsurePasswordIsFresh
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password) {
            return redirect()->route('password.force-edit');
        }

        return $next($request);
    }
}
