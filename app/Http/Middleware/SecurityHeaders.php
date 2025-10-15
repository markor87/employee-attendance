<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Strict-Transport-Security: Force HTTPS
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // X-Frame-Options: Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // X-Content-Type-Options: Prevent MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // X-XSS-Protection: Enable XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer-Policy: Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content-Security-Policy: Control resource loading
        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.bunny.net; " .
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net; " .
            "font-src 'self' https://fonts.bunny.net; " .
            "img-src 'self' data: https:; " .
            "connect-src 'self';"
        );

        // Permissions-Policy: Control browser features
        $response->headers->set('Permissions-Policy',
            'geolocation=(), microphone=(), camera=()'
        );

        return $response;
    }
}
