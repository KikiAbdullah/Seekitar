<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Terminasi HTTPS untuk produksi.
 *
 * Di produksi: redirect HTTP → HTTPS (301) dan set header HSTS. Di balik
 * reverse proxy/load balancer, `trustProxies` wajib diaktifkan agar
 * `$request->isSecure()` membaca header X-Forwarded-Proto dengan benar.
 */
class EnsureHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isProduction() && ! $request->isSecure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
