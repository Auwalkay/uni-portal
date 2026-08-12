<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $currentSession = \App\Services\AcademicCacheService::getCurrentSession();
        $sessionId = $request->input('session_id');

        // Default to current session on first load if no parameter is provided
        if (is_null($sessionId) && $currentSession) {
            $sessionId = $currentSession->id;
        }

        // Date range period logic (default: monthly)
        $period = $request->input('period', 'monthly');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($period !== 'custom') {
            $startDate = match ($period) {
                'daily' => now()->startOfDay()->toDateString(),
                'weekly' => now()->subDays(6)->startOfDay()->toDateString(),
                'monthly' => now()->subDays(29)->startOfDay()->toDateString(),
                'yearly' => now()->subDays(364)->startOfDay()->toDateString(),
                default => now()->subDays(29)->startOfDay()->toDateString(),
            };
            $endDate = now()->endOfDay()->toDateString();
        }

        $filters = $request->only(['search', 'faculty_id', 'department_id', 'status', 'method', 'sort_by', 'sort_order']);
        $filters['session_id'] = $sessionId;
        $filters['period'] = $period;
        $filters['start_date'] = $startDate;
        $filters['end_date'] = $endDate;

        // Check if an export was requested
        if ($request->query('export') === 'reconciliation') {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\PaymentsReconciliationExport($filters),
                'payments_reconciliation_report_' . now()->format('Y_m_d_His') . '.xlsx'
            );
        }

        $query = Payment::query()
            ->select('payments.*')
            ->join('users', 'users.id', '=', 'payments.user_id')
            ->leftJoin('students', 'students.user_id', '=', 'payments.user_id')
            ->with(['invoice.session', 'user.student.academicDepartment.faculty']);

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payments.gateway_reference', 'like', "%{$search}%")
                    ->orWhere('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('students.matriculation_number', 'like', "%{$search}%");
            });
        }

        // Session Filter
        if ($sessionId && $sessionId !== 'ALL_SESSIONS_RESET_VALUE') {
            $query->whereHas('invoice', function ($q) use ($sessionId) {
                $q->where('session_id', $sessionId);
            });
        }

        // Department Filter
        if ($request->filled('department_id') && $request->department_id !== 'ALL_DEPARTMENTS_RESET_VALUE') {
            $query->where('students.department_id', $request->department_id);
        }

        // Faculty Filter
        if ($request->filled('faculty_id') && $request->faculty_id !== 'ALL_FACULTIES_RESET_VALUE' && !$request->filled('department_id')) {
            $query->where('students.faculty_id', $request->faculty_id);
        }

        // Status Filter
        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('payments.status', $request->status);
        }

        // Method Filter (bank payment, manual, card, squadco)
        if ($request->filled('method') && $request->method !== 'ALL') {
            $method = $request->method;
            if ($method === 'manual') {
                $query->where('payments.gateway', 'manual');
            } elseif ($method === 'squadco') {
                $query->where('payments.gateway', 'squadco');
            } elseif ($method === 'bank_transfer') {
                $query->where('payments.channel', 'bank_transfer');
            } elseif ($method === 'card') {
                $query->where('payments.channel', 'card');
            }
        }

        // Date Range Filters (based on payment date)
        if ($startDate) {
            $query->where('payments.paid_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $query->where('payments.paid_at', '<=', $endDate . ' 23:59:59');
        }

        // Sorting
        $sortBy = $request->query('sort_by', 'date');
        $sortOrder = $request->query('sort_order', 'desc');

        if ($sortBy === 'name') {
            $query->orderBy('users.name', $sortOrder);
        } elseif ($sortBy === 'reg_number') {
            $query->orderBy('students.matriculation_number', $sortOrder);
        } elseif ($sortBy === 'status') {
            $query->orderBy('payments.status', $sortOrder);
        } else {
            $query->orderBy('payments.paid_at', $sortOrder);
        }

        $payments = $query->paginate(15)->withQueryString();

        $stats = [
            'total_revenue' => Payment::where('status', 'success')->sum('amount'), // Global Total
            'today_revenue' => Payment::where('status', 'success')->whereDate('paid_at', today())->sum('amount'),
            'successful_count' => Payment::where('status', 'success')->count(),
            'pending_count' => Payment::where('status', 'pending')->count(),
            'failed_count' => Payment::where('status', 'failed')->count(),
        ];

        return \Inertia\Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => $filters,
            'sessions' => \App\Services\AcademicCacheService::getSessions(),
            'faculties' => \App\Services\AcademicCacheService::getFaculties(),
            'departments' => \App\Services\AcademicCacheService::getAllDepartments(),
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

    public function downloadReceipt(Payment $payment)
    {
        if ($payment->status !== 'success') {
            return back()->with('error', 'Only successful payments have receipts.');
        }

        $payment->load(['invoice.session', 'user.student.program']);
        
        $pdf = Pdf::loadView('documents.payment_receipt', [
            'payment' => $payment,
            'student' => $payment->user->student,
            'invoice' => $payment->invoice,
        ])->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => true,
        ]);

        return $pdf->download("Receipt_{$payment->gateway_reference}.pdf");
    }
}
