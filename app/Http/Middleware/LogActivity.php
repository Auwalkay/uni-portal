<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log state-changing requests (POST, PUT, PATCH, DELETE)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            // Skip logging certain sensitive routes or those that don't need logging
            if ($request->routeIs('login', 'logout', 'password.*', 'register')) {
                return $response;
            }

            // Also skip if response was not successful (optional - let's log them but maybe mark them?)
            // For now, only log successful state changes
            if (!$response->isSuccessful()) {
                return $response;
            }

            $user = Auth::user();
            $routeName = $request->route() ? $request->route()->getName() : 'unknown';

            // Try to extract a friendly module name from the route
            $module = 'system';
            if ($routeName) {
                if (str_contains($routeName, 'staff'))
                    $module = 'staff';
                elseif (str_contains($routeName, 'student'))
                    $module = 'students';
                elseif (str_contains($routeName, 'payments') || str_contains($routeName, 'finance'))
                    $module = 'finance';
                elseif (str_contains($routeName, 'settings'))
                    $module = 'settings';
                elseif (str_contains($routeName, 'roles'))
                    $module = 'rbac';
            }

            // Scrub sensitive data from payload
            $details = $request->except(['password', 'password_confirmation', 'old_password', '_token', '_method']);

            AuditLog::create([
                'user_id' => $user ? $user->id : null,
                'action' => strtolower($request->method()),
                'module' => $module,
                'ip_address' => $request->ip(),
                'details' => [
                    'url' => $request->fullUrl(),
                    'route' => $routeName,
                    'payload' => $details,
                    'status_code' => $response->getStatusCode()
                ]
            ]);
        }

        return $response;
    }
}
