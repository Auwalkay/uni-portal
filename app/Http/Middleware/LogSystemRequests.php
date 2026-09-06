<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogSystemRequests
{
    protected float $startTime;

    /**
     * Handle an incoming request and record start time.
     */
    public function handle(Request $request, Closure $next)
    {
        $this->startTime = microtime(true);

        return $next($request);
    }

    /**
     * Perform logging tasks after the response has been sent to the browser.
     * This ensures ZERO impact on page load speed for the user.
     */
    public function terminate(Request $request, $response): void
    {
        $duration = round((microtime(true) - ($this->startTime ?? microtime(true))) * 1000, 2);

        // Skip static assets or internal build paths to avoid log clutter
        $path = $request->path();
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|map)$/i', $path) || str_starts_with($path, 'build/') || str_starts_with($path, '_debugbar')) {
            return;
        }

        // Sanitize sensitive fields in payload
        $input = $request->except([
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'secret_key',
            'token',
            'api_key',
            'credit_card_number',
            'cvv',
        ]);

        $user = $request->user();
        $statusCode = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200;

        // 1. File Logger (storage/logs/laravel.log)
        Log::info('[SYSTEM_REQUEST]', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => '/' . ltrim($path, '/'),
            'route_name' => $request->route()?->getName() ?? 'unnamed_route',
            'status_code' => $statusCode,
            'duration_ms' => "{$duration}ms",
            'ip_address' => $request->ip(),
            'user_id' => $user?->id ?? null,
            'user_email' => $user?->email ?? 'guest',
            'user_role' => $user ? ($user->getRoleNames()->first() ?? 'user') : 'guest',
            'payload' => !empty($input) ? $input : null,
            'user_agent' => substr((string) $request->header('User-Agent'), 0, 150),
        ]);

        // 2. Database Activity Log (Recorded so requests appear on Admin Dashboard Activity Logs)
        try {
            if (function_exists('activity')) {
                $activityLogger = activity('system_request')
                    ->event($request->method())
                    ->withProperties([
                        'method' => $request->method(),
                        'url' => $request->fullUrl(),
                        'path' => '/' . ltrim($path, '/'),
                        'route_name' => $request->route()?->getName() ?? 'unnamed_route',
                        'status_code' => $statusCode,
                        'duration_ms' => "{$duration}ms",
                        'ip_address' => $request->ip(),
                        'user_email' => $user?->email ?? 'guest',
                        'user_role' => $user ? ($user->getRoleNames()->first() ?? 'user') : 'guest',
                        'payload' => !empty($input) ? $input : null,
                    ]);

                if ($user) {
                    $activityLogger->causedBy($user);
                }

                $activityLogger->log("{$request->method()} /" . ltrim($path, '/'));
            }
        } catch (\Throwable $e) {
            // Catch silently so logging never interrupts user requests
        }
    }
}
