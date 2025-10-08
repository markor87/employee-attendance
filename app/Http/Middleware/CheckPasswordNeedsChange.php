<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordNeedsChange
{
    /**
     * Handle an incoming request.
     *
     * Redirect to force change password page if PasswordNeedsChange flag is set.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Skip check if user is not authenticated
        if (!$user) {
            return $next($request);
        }

        // Skip check if already on change password routes
        if ($request->routeIs('password.change.*') || $request->routeIs('password.force.*')) {
            return $next($request);
        }

        // Skip check for logout route
        if ($request->routeIs('logout') || $request->routeIs('admin.logout')) {
            return $next($request);
        }

        // Redirect to force change password page if flag is set
        if ($user->PasswordNeedsChange) {
            return redirect()->route('password.force.show')->with('warning', 'Морате променити лозинку пре наставка.');
        }

        return $next($request);
    }
}
