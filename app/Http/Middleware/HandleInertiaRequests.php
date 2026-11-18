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

            $studentData = \Illuminate\Support\Facades\Cache::remember(
                "user_auth_student_{$user->id}",
                300,
                function () use ($user) {
                    $student = $user->student()->with(['programme', 'program', 'pgProfile'])->first();
                    if (! $student) {
                        return null;
                    }

                    return [
                        'id' => $student->id,
                        'department_id' => $student->effective_department_id,
                        'program_id' => $student->program_id,
                        'is_postgraduate' => $student->isPostgraduate(),
                        'academic_stream' => $student->academic_stream ?? $student->programme?->academic_stream ?? 'UG',
                        'current_level' => $student->current_level,
                        'matriculation_number' => $student->matriculation_number,
                        'spgs_file_number' => $student->pgProfile?->spgs_file_number,
                        'candidacy_status' => $student->pgProfile?->candidacy_status,
                        'study_mode' => $student->pgProfile?->study_mode,
                        'degree_type' => $student->programme?->type ?? $student->program?->type,
                        'programme_name' => $student->programme?->name ?? $student->program?->name,
                    ];
                }
            );

            $authData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_path' => $user->profile_photo_path,
                'roles' => $userRolesAndPerms['roles'],
                'permissions' => $userRolesAndPerms['permissions'],
                'student' => $studentData,
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
            'ziggy' => \Inertia\Inertia::once(function () use ($request) {
                return [
                    'location' => $request->url(),
                    'url' => $request->schemeAndHttpHost(),
                    'port' => (int) $request->getPort() ?: null,
                ];
            }),
        ];
    }
}
