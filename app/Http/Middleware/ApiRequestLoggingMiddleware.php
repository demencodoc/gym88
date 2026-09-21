<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestLoggingMiddleware
{
    /**
     * Handle an incoming request and log details with response metrics.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Sanitize sensitive request parameters
        $sanitizedInput = $request->except(['password', 'password_confirmation', 'token', 'signature']);

        $response = $next($request);

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);
        $statusCode = $response->getStatusCode();
        $userId = $request->user()?->id ?? 'guest';

        $logData = [
            'method' => $request->method(),
            'uri' => $request->getRequestUri(),
            'status' => $statusCode,
            'duration_ms' => $durationMs,
            'user_id' => $userId,
            'ip' => $request->ip(),
            'input' => $sanitizedInput,
        ];

        if ($statusCode >= 500) {
            Log::error("API [{$request->method()}] {$request->getRequestUri()} Failed", $logData);
        } elseif ($statusCode >= 400) {
            Log::warning("API [{$request->method()}] {$request->getRequestUri()} Client Warning", $logData);
        } else {
            Log::info("API [{$request->method()}] {$request->getRequestUri()} Success", $logData);
        }

        return $response;
    }
}
