<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Check if we're in development mode with Vite
        $viteDevServer = '';
        $viteConnectSrc = '';

        if (app()->environment('local', 'development')) {
            $vitePort = env('VITE_PORT', 5173);
            $viteDevServer = " http://localhost:{$vitePort}";
            $viteConnectSrc = " http://localhost:{$vitePort} ws://localhost:{$vitePort}";
        }

        // Get all unique stream hosts from radio stations (cached for 1 hour)
        // Use try-catch to gracefully handle cases where the stream_host column doesn't exist
        // Cache key includes version to force refresh after fix deployment
        $streamHosts = Cache::remember('radio_station_stream_hosts_v2', 3600, function (): array {
            try {
                return DB::table('radio_stations')
                    ->whereNotNull('stream_host')
                    ->distinct()
                    ->pluck('stream_host')
                    ->toArray();
            } catch (\Illuminate\Database\QueryException $e) {
                // If column doesn't exist or query fails, return empty array
                // This can happen if the migration hasn't run yet
                return [];
            }
        });

        $streamHostsStr = !empty($streamHosts) ? ' ' . implode(' ', $streamHosts) : '';

        // Build CSP directives
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://app.lemonsqueezy.com{$viteDevServer}",
            "style-src 'self' 'unsafe-inline'{$viteDevServer}",
            "img-src 'self' data: https: blob:{$viteDevServer}",
            "font-src 'self' data:",
            "connect-src 'self' wss: ws:{$viteConnectSrc}",
            "media-src 'self' blob:{$streamHostsStr}",
            "object-src 'none'",
            "frame-src 'self' https://docs.google.com https://*.google.com",
            "child-src 'self' https://docs.google.com https://*.google.com",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        return $response;
    }
}
