<?php

namespace App\Http\Middleware;

use App\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Check2FA
{
    protected $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Handle an incoming request.
     *
     * Redirect to 2FA verification page if code exists but not verified.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check if user is authenticated
        if (Auth::check()) {
            return $next($request);
        }

        // Skip check if already on 2FA routes
        if ($request->routeIs('2fa.*')) {
            return $next($request);
        }

        // Skip check for login and logout routes
        if ($request->routeIs('login') || $request->routeIs('login.show') || $request->routeIs('logout')) {
            return $next($request);
        }

        // Check if there's a pending 2FA code
        if ($this->twoFactorService->hasValidCode() && !$this->twoFactorService->isVerified()) {
            return redirect()->route('2fa.show');
        }

        return $next($request);
    }
}
