<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Powers the "online" indicator on the admin's student list. Registered
 * globally on the web group (not scoped to student-app/staff) so it covers
 * every role — a dean's own activity should show up to the master admin
 * too, not just students'.
 */
class TrackLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->user()?->markSeen();

        return $next($request);
    }
}
