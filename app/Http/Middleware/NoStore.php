<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NoStore
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);
        // Laravel skips AJAX URLs; Inertia navigation still needs a safe return
        // location when Referrer-Policy intentionally suppresses Referer.
        if ($request->isMethod('GET') && $request->header('X-Inertia') && $response->isSuccessful() && ! $request->prefetch() && $request->hasSession()) {
            $request->session()->setPreviousUrl($request->fullUrl());
        }
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
