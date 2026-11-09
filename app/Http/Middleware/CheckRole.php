<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request and verify user role authorization.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('login'));
        }

        if (! $user->is_active) {
            abort(403, 'Your user account has been deactivated.');
        }

        $user->loadMissing('role');

        if (! $user->hasRole($roles)) {
            abort(403, 'Unauthorized role for this module.');
        }

        return $next($request);
    }
}
