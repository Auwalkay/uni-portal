<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'tenant' => function () {
                $tenant = tenant();
                if (!$tenant)
                    return null;

                return [
                    'name' => $tenant->school_name,
                    'logo' => $tenant->logo_path ? global_asset('storage/' . $tenant->logo_path) : null,
                ];
            },
            'name' => config('app.name'),
            'auth' => function () use ($request) {
                $user = null;

                // If we are on a tenant domain, use the default web guard (which safely checks the tenant DB)
                // If we are on the central domain, use the central guard.
                if (tenant()) {
                    $user = $request->user('web');
                } else {
                    $user = $request->user('central');
                }

                return [
                    'user' => $user ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [],
                        'permissions' => method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name') : [],
                    ] : null,
                ];
            },
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'warning' => fn() => $request->session()->get('warning'),
                'info' => fn() => $request->session()->get('info'),
                'status' => fn() => $request->session()->get('status'),
            ],
            'ziggy' => function () use ($request) {
                return array_merge((new \Tighten\Ziggy\Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
        ];
    }
}
