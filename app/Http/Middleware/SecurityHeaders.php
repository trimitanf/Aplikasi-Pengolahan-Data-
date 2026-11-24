<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menambahkan HTTP security headers standar pada setiap respons.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;

        // Cegah halaman dimuat di dalam iframe situs lain (clickjacking)
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        // Cegah browser menebak tipe konten (MIME sniffing)
        $headers->set('X-Content-Type-Options', 'nosniff');
        // Batasi informasi URL yang dikirim ke situs lain
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Nonaktifkan fitur browser yang tidak dibutuhkan aplikasi
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // HSTS hanya bila diakses via HTTPS
        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Halaman yang memerlukan login tidak boleh disimpan di cache browser/proxy
        if ($request->user()) {
            $headers->set('Cache-Control', 'no-store, private');
        }

        $headers->remove('X-Powered-By');

        return $response;
    }
}
