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

        return \Inertia\Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => $filters,
            'sessions' => \App\Models\Session::latest()->get(['id', 'name']),
            'faculties' => \App\Models\Faculty::with('departments:id,name,faculty_id')->get(['id', 'name']),
            'departments' => \App\Models\Department::get(['id', 'name', 'faculty_id']),
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
