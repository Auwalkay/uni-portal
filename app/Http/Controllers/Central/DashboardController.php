<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Tenant;

class DashboardController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with(['domains', 'subscriptions'])->latest()->get();

        $platformInsights = [
            'total_students' => 0,
            'total_staff' => 0,
            'total_applications' => 0,
            'total_courses' => 0,
            'total_departments' => 0,
            'total_revenue' => \App\Models\TenantSubscription::where('status', 'active')->sum('amount'),
            'active_tenants' => $tenants->where('is_active', true)->count(),
            'expiring_soon' => \App\Models\TenantSubscription::where('status', 'active')
                ->where('end_date', '<=', \Carbon\Carbon::now()->addDays(30))
                ->with('tenant')
                ->get(),
        ];

        // We will map over tenants to append their individual insights
        // and aggregate global numbers.
        $enrichedTenants = $tenants->map(function ($tenant) use (&$platformInsights) {
            /** @var \App\Models\Tenant $tenant */
            $stats = $tenant->run(function () {
                return [
                    'students' => \App\Models\Student::count(),
                    'staff' => \App\Models\Staff::count(),
                    'applications' => \App\Models\Applicant::count(),
                    'courses' => \App\Models\Course::count(),
                    'departments' => \App\Models\Department::count(),
                ];
            });

            $platformInsights['total_students'] += $stats['students'];
            $platformInsights['total_staff'] += $stats['staff'];
            $platformInsights['total_applications'] += $stats['applications'];
            $platformInsights['total_courses'] += $stats['courses'];
            $platformInsights['total_departments'] += $stats['departments'];

            // Attach the scoped statistics back to the tenant object array
            $tenantArray = $tenant->toArray();
            $tenantArray['insights'] = $stats;

            return $tenantArray;
        });

        // Sort enriched tenants by student count descending as a "Top" metric
        $enrichedTenants = $enrichedTenants->sortByDesc('insights.students')->values();

        // Recent Activity Feed (Sample - can be expanded to real audit logs later)
        $recentActivity = [
            'registrations' => Tenant::latest()->take(5)->get()->map(fn($t) => [
                'type' => 'REGISTRATION',
                'title' => $t->school_name ?? $t->id,
                'description' => 'New polytechnic onboarded',
                'date' => $t->created_at->diffForHumans(),
            ]),
            'payments' => \App\Models\TenantSubscription::with('tenant')->latest()->take(5)->get()->map(fn($s) => [
                'type' => 'PAYMENT',
                'title' => $s->tenant->school_name ?? $s->tenant->id,
                'description' => 'Annual subscription paid: ' . number_format($s->amount, 2),
                'date' => $s->created_at->diffForHumans(),
            ]),
        ];

        return Inertia::render('Central/Dashboard', [
            'tenants' => $enrichedTenants,
            'platformInsights' => $platformInsights,
            'recentActivity' => $recentActivity,
        ]);
    }
}
