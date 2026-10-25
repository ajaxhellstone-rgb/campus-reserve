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

        // Remove headers that leak info about server software being used
        // Harder for attackers to find vulnerabilities specific to this setup

        // Check if working with a Response object before adding headers
        if ($response instanceof \Illuminate\Http\Response || $response instanceof Response) {
            // Prevent site from being framed in other websites
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            
            // Prevent browsers from guessing what type of content is being sent
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            
            // Set security policies for what resources can be accessed
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            
            // Tell browsers not to cache sensitive pages, prevents showing old versions
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            
            // Set up strong Content Security Policy rules
            // Controls what resources (scripts, images, etc) can load on this site
            $frameAncestors = $request->is('calendar/manage') && $request->boolean('embedded')
                ? "frame-ancestors 'self'"
                : "frame-ancestors 'none'";

            $cspDirectives = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
                "img-src 'self' data:",
                "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net",
                "connect-src 'self' https://fonts.googleapis.com https://cdn.jsdelivr.net",
                "form-action 'self'",
                "base-uri 'self'",
                "object-src 'none'",
                $frameAncestors,
                'block-all-mixed-content',
            ];

            if (config('app.debug') || app()->environment('local')) {
                $devOrigins = [
                    'http://127.0.0.1:5173',
                    'http://localhost:5173',
                ];

                $httpDevOrigins = implode(' ', $devOrigins);
                $wsDevOrigins = implode(' ', array_map(fn ($origin) => preg_replace('#^http#', 'ws', $origin), $devOrigins));

                $cspDirectives[1] .= ' ' . $httpDevOrigins;
                $cspDirectives[2] .= ' ' . $httpDevOrigins;
                $cspDirectives[5] .= ' ' . $httpDevOrigins . ' ' . $wsDevOrigins;
            }

            $response->headers->set('Content-Security-Policy', implode('; ', $cspDirectives) . ';');

            // HSTS (Strict-Transport-Security) always set
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
