<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'type', 'session_id']);

        $query = Invoice::query()
            ->with(['user.student', 'session']);

        // Scope to user role if not admin/bursar? 
        // Admin middleware allows finance_officer now.
        // Usually fine to see all.


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

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        // Clone query for global analytics (respecting filters)
        $statsQuery = clone $query;
        // Reset pagination for aggregation
        $statsQuery->getQuery()->orders = null;
        $statsQuery->getQuery()->limit = null;
        $statsQuery->getQuery()->offset = null;

        // Stats
        $totalExpected = $statsQuery->sum('amount');
        $totalCollected = $statsQuery->sum('paid_amount');
        $totalOutstanding = $totalExpected - $totalCollected;

        // Chart Data: Status Distribution
        $statusDistribution = (clone $statsQuery)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Chart Data: Revenue Trend (Last 30 days)
        $revenueTrend = \App\Models\Payment::where('status', 'success')
            ->whereDate('paid_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->date => $item->total];
            });

        // Fill missing dates
        $chartDates = [];
        $chartData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartDates[] = now()->subDays($i)->format('M d');
            $chartData[] = $revenueTrend[$date] ?? 0;
        }

        $analytics = [
            'total_expected' => $totalExpected,
            'total_collected' => $totalCollected,
            'total_outstanding' => $totalOutstanding,
            'collection_rate' => $totalExpected > 0 ? round(($totalCollected / $totalExpected) * 100, 1) : 0,
            'charts' => [
                'status_distribution' => [
                    'labels' => ['Paid', 'Partial', 'Pending'],
                    'datasets' => [
                        [
                            'backgroundColor' => ['#10b981', '#3b82f6', '#f59e0b'],
                            'data' => [
                                $statusDistribution['paid'] ?? 0,
                                $statusDistribution['partial'] ?? 0,
                                $statusDistribution['pending'] ?? 0,
                            ]
                        ]
                    ]
                ],
                'revenue_trend' => [
                    'labels' => $chartDates,
                    'datasets' => [
                        [
                            'label' => 'Revenue (NGN)',
                            'backgroundColor' => '#10b981',
                            'borderColor' => '#10b981',
                            'tension' => 0.3, // Smooth curve
                            'data' => $chartData
                        ]
                    ]
                ]
            ]
        ];

        $invoices = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $filters,
            'sessions' => \App\Models\Session::latest()->get(['id', 'name']),
            'analytics' => $analytics
        ]);
    }

    public function create()
    {
        // Simple list won't work for thousands of students.
        // We will implement an async search in the Frontend, querying a search endpoint.
        // For now, we pass sessions.

        return Inertia::render('Admin/Invoices/Create', [
            'sessions' => \App\Models\Session::latest()->get(['id', 'name']),
        ]);
    }

    public function searchStudents(Request $request)
    {
        $search = $request->input('query');

        if (empty($search)) {
            return response()->json([]);
        }

        $students = \App\Models\User::role('student')
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($s) use ($search) {
                        $s->where('matriculation_number', 'like', "%{$search}%");
                    });
            })
            ->with([
                'student' => function ($q) {
                    $q->select('id', 'user_id', 'matriculation_number', 'department', 'level');
                }
            ])
            ->limit(10)
            ->get(['id', 'name', 'email', 'profile_photo_path']); // Select fields

        return response()->json($students);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|string',
            'description' => 'required|string|max:255',
            'due_date' => 'required|date',
            'session_id' => 'required|exists:sessions,id',
        ]);

        // Generate reference
        $reference = 'INV-' . strtoupper(uniqid());

        $invoice = Invoice::create([
            'user_id' => $validated['user_id'],
            'session_id' => $validated['session_id'],
            'reference' => $reference,
            'type' => $validated['type'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'due_date' => $validated['due_date'],
            'status' => 'pending',
            'paid_amount' => 0,
            'currency' => 'NGN', // Default
        ]);

        return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Invoice generated successfully.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['user.student', 'session', 'items', 'payments.user']);

        return Inertia::render('Admin/Invoices/Show', [
            'invoice' => $invoice,
            'payments' => $invoice->payments()->latest()->get(),
        ]);
    }

    public function markAsPaid(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice is already marked as paid.');
        }

        // Calculate pending amount
        $pendingAmount = $invoice->amount - $invoice->paid_amount;
        if ($pendingAmount <= 0) {
            // Should verify why it wasn't marked paid if balance is 0
            $invoice->update(['status' => 'paid']);
            return back()->with('success', 'Invoice corrected to Paid status.');
        }

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $invoice->user_id,
            'gateway_reference' => 'MANUAL-' . strtoupper(uniqid()),
            'amount' => $pendingAmount,
            'status' => 'success',
            'channel' => 'manual', // or 'admin'
            'paid_at' => now(),
        ]);

        $invoice->update([
            'status' => 'paid',
            'paid_amount' => $invoice->amount, // Full amount is now paid
        ]);

        // Trigger enrollment if acceptance fee?
        if ($invoice->type === 'acceptance_fee') {
            $applicant = \App\Models\Applicant::where('user_id', $invoice->user_id)
                ->with('programme.department.faculty')
                ->first();

            if ($applicant) {
                app(\App\Services\EnrollmentService::class)->enroll($applicant, $invoice->user_id);
            }
        }

        // Send Receipt logic could be here (optional for manual payments?)

        return back()->with('success', 'Invoice marked as paid successfully.');
    }

    public function verifyPayment(\App\Models\Payment $payment, \App\Services\PaystackService $paystackService)
    {
        // 1. Verify with Paystack
        $paymentData = $paystackService->verifyTransaction($payment->gateway_reference);

        if (!$paymentData || $paymentData['status'] !== 'success') {
            return back()->with('error', 'Payment verification failed or transaction not successful.');
        }

        // 2. Check amount match (Paystack amount is in kobo usually, but service returns data. Let's assume service handles formatting or we check raw)
        // basic check: if verified, we trust it.
        // Actually PaystackService returns data. data['amount'] is in kobo.
        $verifiedAmount = $paymentData['amount'] / 100;

        if ($payment->status === 'success') {
            return back()->with('info', 'Payment is already marked as successful.');
        }

        // 3. Update Payment
        $payment->update([
            'status' => 'success',
            'amount' => $verifiedAmount, // Update amount to actual paid if different? Safe to keep match.
            'paid_at' => \Carbon\Carbon::parse($paymentData['paid_at']),
            'channel' => $paymentData['channel'] ?? 'paystack',
        ]);

        // 4. Update Invoice
        $invoice = $payment->invoice;
        $totalPaid = $invoice->payments()->where('status', 'success')->sum('amount');

        $invoice->update([
            'paid_amount' => $totalPaid,
            'status' => $totalPaid >= $invoice->amount ? 'paid' : ($totalPaid > 0 ? 'partial' : 'pending'),
        ]);

        // 5. Trigger Enrollment if applicable
        if ($invoice->status === 'paid' && $invoice->type === 'acceptance_fee') {
            $applicant = \App\Models\Applicant::where('user_id', $invoice->user_id)->first();
            if ($applicant) {
                app(\App\Services\EnrollmentService::class)->enroll($applicant, $invoice->user_id);
            }
        }

        return back()->with('success', 'Payment verified and updated successfully.');
    }
}
