<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // HSTS — force HTTPS for 1 year, include subdomains
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // Prevent MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking — never allow framing
        $response->headers->set('X-Frame-Options', 'DENY');

        // Referrer — send origin only on cross-origin, full on same-origin
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // XSS Protection — legacy browsers (still blocks some attacks)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Permissions Policy — disable unused browser features
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()');

        // Cross-Origin Policies — isolate browsing context
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        // CSP — Livewire needs unsafe-inline for inline styles, unsafe-eval for Alpine
        // TODO: migrate to nonce-based CSP when Livewire supports it
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net",
            "img-src 'self' data: blob: https://*.tile.openstreetmap.org",
            "font-src 'self' data: https://fonts.googleapis.com https://fonts.gstatic.com https://fonts.bunny.net",
            "connect-src 'self' wss: ws:",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
