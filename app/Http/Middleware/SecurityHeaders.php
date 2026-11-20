<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response=$next($request);

        // The careers widget (and the application form it posts) is embedded on other websites.
        $embeddable=$request->is('careers/embed', 'careers/embed/*', 'careers/*/apply');

        $response->headers->set('X-Content-Type-Options','nosniff');
        if ($embeddable) {
            $response->headers->remove('X-Frame-Options');
        } else {
            $response->headers->set('X-Frame-Options','SAMEORIGIN');
        }
        $response->headers->set('Referrer-Policy','strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy','camera=(), microphone=(), geolocation=(self)');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; frame-ancestors ".($embeddable ? '*' : "'self'").';'
        );

        return $response;
    }
}
