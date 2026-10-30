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
        $user = $request->user();
        $authData = null;

        if ($user) {
            $userRolesAndPerms = \Illuminate\Support\Facades\Cache::remember(
                "user_auth_roles_perms_{$user->id}",
                120,
                fn() => [
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ]
            );

            $authData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_path' => $user->profile_photo_path,
                'roles' => $userRolesAndPerms['roles'],
                'permissions' => $userRolesAndPerms['permissions'],
                'student' => $user->student ? [
                    'id' => $user->student->id,
                    'department_id' => $user->student->effective_department_id,
                    'program_id' => $user->student->program_id,
                ] : null,
            ];
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'settings' => \App\Services\AcademicCacheService::getSystemSettings(),
            'auth' => [
                'user' => $authData,
            ],
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'warning' => fn() => $request->session()->get('warning'),
                'info' => fn() => $request->session()->get('info'),
                'status' => fn() => $request->session()->get('status'),
                'verified_candidate' => fn() => $request->session()->get('verified_candidate'),
            ],
            'ziggy' => function () use ($request) {
                return array_merge((new \Tighten\Ziggy\Ziggy)->toArray(), [
                    'location' => $request->url(),
                    'url' => $request->schemeAndHttpHost(),
                    'port' => (int) $request->getPort() ?: null,
                ]);
            },
        ];
    }
}
