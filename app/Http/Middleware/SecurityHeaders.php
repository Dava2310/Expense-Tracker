<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds browser security headers to every response.
 */
class SecurityHeaders
{
    /**
     * Adds the standard browser security headers to every response.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // The browser must trust the declared content type instead of guessing it
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // No other site can show Saldo inside a frame
        $response->headers->set('X-Frame-Options', 'DENY');

        // Other sites only learn the origin of a link that was clicked, never the full address
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Saldo never needs the camera, the microphone or the location
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Force HTTPS for a year, but only where HTTPS really is in use
        if (app()->isProduction() && $request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
