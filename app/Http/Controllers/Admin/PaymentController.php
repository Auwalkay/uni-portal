<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'session_id', 'faculty_id', 'department_id']);

        $query = \App\Models\Payment::query()
            ->with(['invoice.session', 'user.student.academicDepartment.faculty']);

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhereHas('student', function ($s) use ($search) {
                                $s->where('matriculation_number', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // Session Filter
        if ($request->filled('session_id')) {
            $query->whereHas('invoice', function ($q) use ($request) {
                $q->where('session_id', $request->session_id);
            });
        }

        // Department Filter
        if ($request->filled('department_id')) {
            $query->whereHas('user.student', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        // Faculty Filter (if department is not selected, or to narrow down departments)
        if ($request->filled('faculty_id') && !$request->filled('department_id')) {
            $query->whereHas('user.student.academicDepartment', function ($q) use ($request) {
                $q->where('faculty_id', $request->faculty_id);
            });
        }

        $payments = $query->latest()->paginate(15)->withQueryString();

        // Calculate Stats (Respecting filters would be cool, but global stats are usually expected on top unless specified. 
        // Let's do Global Stats for general overview, or Filtered Stats? 
        // Let's do Filtered Stats so they update as you filter.

        // Clone query for stats to avoid messing up pagination
        $statsQuery = clone $query;
        // Removing ordering and pagination for aggregation
        $statsQuery->getQuery()->orders = null;

        // However, cloning the builder with Eager Loading might be heavy if we just want aggregates.
        // Let's optimize by just using the base filter logic without eager loads for stats.
        // Re-applying filters to a fresh query is cleaner.

        $stats = [
            'total_revenue' => \App\Models\Payment::where('status', 'paid')->sum('amount'), // Global Total
            'today_revenue' => \App\Models\Payment::where('status', 'paid')->whereDate('paid_at', today())->sum('amount'),
            'successful_count' => \App\Models\Payment::where('status', 'paid')->count(),
            'pending_count' => \App\Models\Payment::where('status', 'pending')->count(),
            'failed_count' => \App\Models\Payment::where('status', 'failed')->count(),
        ];

        return \Inertia\Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => $filters,
            'sessions' => \App\Models\Session::latest()->get(['id', 'name']),
            'faculties' => \App\Models\Faculty::with('departments:id,name,faculty_id')->get(['id', 'name']),
            'departments' => \App\Models\Department::get(['id', 'name', 'faculty_id']),
            'stats' => $stats,
        ]);
    }
    public function show(\App\Models\Payment $payment)
    {
        $payment->load(['user.student.academicDepartment.faculty', 'invoice.session', 'invoice.items']);

        return \Inertia\Inertia::render('Admin/Payments/Show', [
            'payment' => $payment
        ]);
    }
}
