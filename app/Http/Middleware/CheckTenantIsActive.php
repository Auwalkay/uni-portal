<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;

class CheckTenantIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        // If 'is_active' is explicitly false, block access. (null or true means active)
        if ($tenant && isset($tenant->is_active) && $tenant->is_active === false) {
            return Inertia::render('Errors/Suspended', [
                'schoolName' => $tenant->school_name ?? $tenant->id,
            ])->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}
