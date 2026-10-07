<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deans have no personal financial data and are scoped to their faculty's
 * users/categories panel only — block them from the student-facing app
 * (admin master is still allowed in, via the deliberate "Ver app de
 * estudiante" link in their sidebar).
 */
class EnsureUserIsStudentApp
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isDean()) {
            return redirect()->route('admin.users.index');
        }

        return $next($request);
    }
}
