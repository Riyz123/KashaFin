<?php

namespace App\Http\Middleware;

use App\Models\FeatureFlag;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates an entire route group behind a FeatureFlag key (`->middleware('feature:expenses')`).
 * Never deletes or hides data — a disabled section's existing rows stay
 * exactly as they are (in the database, in reports, in the activity log);
 * this only blocks the routes that would let someone view or change them
 * while the admin has it turned off. A GET gets the friendly "disabled"
 * page instead of a 404/403; anything else (store/update/delete, or a
 * request that expects JSON) gets turned back without side effects.
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        if (FeatureFlag::isEnabled($key)) {
            return $next($request);
        }

        $message = FeatureFlag::disabledMessage($key);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        if ($request->isMethod('GET')) {
            return response()->view('feature-disabled', ['message' => $message], 200);
        }

        return redirect()->route('dashboard')->with('status', $message);
    }
}
