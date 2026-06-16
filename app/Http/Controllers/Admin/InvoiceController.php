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

        $sort = $request->input('order', $request->input('sort', 'desc'));
        $query->orderBy('created_at', $sort);

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

        $invoices = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $filters,
            'sessions' => fn() => \App\Models\Session::latest()->get(['id', 'name']),
            'analytics' => fn() => $analytics,
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

    public function markAsPaid(Request $request, Invoice $invoice)
    {
        if (!Auth::user()->can('manual_payment_override')) {
            abort(403, 'You do not have permission to manually override payments.');
        }

        $request->validate([
            'amount' => 'nullable|numeric|min:1|max:' . ($invoice->amount - $invoice->paid_amount),
        ]);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice is already marked as paid.');
        }

        $balance = $invoice->amount - $invoice->paid_amount;
        $amountToRecord = $request->amount ?? $balance;

        if ($amountToRecord <= 0) {
            return back()->with('error', 'Invalid amount.');
        }

        $payment = Payment::create([
            'transaction_id' => 'TRX-' . strtoupper(uniqid()),
            'invoice_id' => $invoice->id,
            'user_id' => $invoice->user_id,
            'recorded_by' => Auth::id(),
            'gateway_reference' => 'MANUAL-' . strtoupper(uniqid()),
            'amount' => $amountToRecord,
            'status' => 'success',
            'channel' => 'manual',
            'paid_at' => now(),
        ]);

        $newTotalPaid = $invoice->paid_amount + $amountToRecord;
        $newStatus = $newTotalPaid >= $invoice->amount ? 'paid' : 'partial';

        $invoice->update([
            'status' => $newStatus,
            'paid_amount' => $newTotalPaid,
        ]);

        // Trigger side-effects if now fully paid
        if ($newStatus === 'paid') {
            if ($invoice->type === 'acceptance_fee') {
                $applicant = \App\Models\Applicant::where('user_id', $invoice->user_id)
                    ->first();

                if ($applicant) {
                    app(\App\Services\EnrollmentService::class)->enroll($applicant, $invoice->user_id);
                }
            }

            if ($invoice->type === 'hostel_fee') {
                $booking = \App\Models\HostelBooking::where('invoice_id', $invoice->id)->first();
                if ($booking) {
                    $booking->update(['status' => 'confirmed']);
                }
            }

            if ($invoice->type === 'application_fee') {
                $applicant = \App\Models\Applicant::where('user_id', $invoice->user_id)->first();
                if ($applicant && $applicant->status === 'pending_payment') {
                    $applicant->update([
                        'status' => 'submitted',
                        'application_number' => \App\Helpers\ApplicationNumberHelper::generate(),
                    ]);
                    
                    $invoice->user->notify(new \App\Notifications\ApplicationSubmitted($applicant));
                }
            }
        }

        return back()->with('success', 'Manual payment recorded successfully.');
    }



    public function verifyPayment(\App\Models\Payment $payment)
    {
        // Resolve the correct gateway service based on the payment's gateway field
        $gatewayName = $payment->gateway ?? 'squadco';

        if ($gatewayName === 'paystack') {
            $gatewayService = app(\App\Services\PaystackService::class);
        } else {
            $gatewayService = app(\App\Services\SquadcoService::class);
        }

        // 1. Verify with the gateway
        $paymentData = $gatewayService->verifyTransaction($payment->gateway_reference);

        // $data = $this->gateway->verifyTransaction($reference);

        if ($paymentData && $paymentData['status'] === 'success') {
            $payment = Payment::where('gateway_reference', $payment->gateway_reference)->first();

            if ($payment && $payment->status !== 'success') {
                app(\App\Services\Payment\PaymentHandler::class)->handleSuccessfulPayment($payment->gateway_reference, $paymentData);
            }

            // return redirect()->route('applicant.apply.show')->with('success', 'Payment successful! Application submitted.');
        }

        if (!$paymentData || $paymentData['status'] !== 'success') {
            $statusMsg = $paymentData['status'] ?? 'no response';
            return back()->with('error', "Payment verification failed. Gateway status: {$statusMsg}.");
        }

        if ($payment->status === 'success') {
            return back()->with('info', 'Payment is already marked as successful.');
        }

        return back()->with('success', 'Payment verified and updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->paid_amount > 0 || $invoice->payments()->count() > 0) {
            return back()->with('error', 'Cannot delete an invoice that has payments attached to it.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($invoice) {
            $invoice->items()->delete();
            $invoice->delete();
        });

        return back()->with('success', 'Invoice deleted successfully.');
    }
}
