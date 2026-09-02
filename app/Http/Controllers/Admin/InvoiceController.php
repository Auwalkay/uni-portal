<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'type', 'session_id', 'sort_field', 'sort_order', 'order']);

        $query = Invoice::query()
            ->with(['user.student', 'session', 'creator', 'updater']);

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

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->filled('session_id') && $request->session_id !== 'all') {
            $query->where('session_id', $request->session_id);
        }

        $sortField = $request->input('sort_field', 'created_at');
        $sortOrder = strtolower($request->input('sort_order', $request->input('order', 'desc')));

        $allowedSortFields = ['created_at', 'due_date', 'amount', 'paid_amount', 'reference', 'status', 'type', 'student', 'session', 'balance'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        // Clone query for global analytics (respecting filters, without list sorting or joins)
        $statsQuery = clone $query;

        // Apply sorting to the list query
        if ($sortField === 'balance') {
            $query->orderByRaw('(invoices.amount - COALESCE(invoices.paid_amount, 0)) ' . $sortOrder);
        } elseif ($sortField === 'student') {
            $query->leftJoin('users', 'invoices.user_id', '=', 'users.id')
                ->select('invoices.*')
                ->orderBy('users.name', $sortOrder);
        } elseif ($sortField === 'session') {
            $query->leftJoin('academic_sessions', 'invoices.session_id', '=', 'academic_sessions.id')
                ->select('invoices.*')
                ->orderBy('academic_sessions.name', $sortOrder);
        } else {
            $query->orderBy("invoices.{$sortField}", $sortOrder);
        }

        // Stats
        $totalExpected = (clone $statsQuery)->sum('invoices.amount');
        $totalCollected = (clone $statsQuery)->sum('invoices.paid_amount');
        $totalOutstanding = $totalExpected - $totalCollected;

        // Chart Data: Status Distribution
        $statusDistribution = (clone $statsQuery)
            ->reorder()
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
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'type' => $request->input('type', ''),
                'session_id' => $request->input('session_id', ''),
                'sort_field' => $sortField,
                'sort_order' => $sortOrder,
            ],
            'sessions' => fn() => \App\Models\Session::latest()->get(['id', 'name']),
            'analytics' => fn() => $analytics,
        ]);
    }

    public function create()
    {
        if (!Auth::user()->can('create_invoices') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized. You need the create_invoices permission to generate invoices.');
        }

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
                    $q->select('id', 'user_id', 'matriculation_number', 'department_id', 'current_level')->with('department');
                }
            ])
            ->limit(10)
            ->get(['id', 'name', 'email', 'profile_photo_path']); // Select fields

        return response()->json($students);
    }

    public function calculateFee(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'session_id' => 'required|exists:academic_sessions,id',
            'type' => 'required|string',
        ]);

        $user = \App\Models\User::findOrFail($validated['user_id']);
        $student = $user->student;
        $session = \App\Models\Session::findOrFail($validated['session_id']);

        if (!$student) {
            return response()->json(['amount' => 0, 'description' => '']);
        }

        if ($validated['type'] === 'school_fee') {
            $targetSessionId = ($student->fee_policy === 'admission_session' && $student->admitted_session_id) 
                ? $student->admitted_session_id 
                : $session->id;

            $allConfigs = \App\Models\FeeConfiguration::where('session_id', $targetSessionId)
                ->where(function ($q) use ($student) {
                    $q->where('level', $student->current_level)
                        ->orWhereNull('level');
                })
                ->where(function ($q) use ($student) {
                    $q->where('entry_mode', $student->entry_mode)
                        ->orWhereNull('entry_mode');
                })
                ->where('is_compulsory', true)
                ->with('feeType')
                ->get();

            $resolvedConfigs = collect();
            $groupedConfigs = $allConfigs->groupBy('fee_type_id');

            foreach ($groupedConfigs as $feeTypeId => $configs) {
                $resolved = null;
                if ($student->program_id) {
                    $resolved = $configs->where('program_id', $student->program_id)->first();
                }
                if (!$resolved && $student->department_id) {
                    $resolved = $configs->where('department_id', $student->department_id)
                        ->whereNull('program_id')
                        ->first();
                }
                if (!$resolved && $student->faculty_id) {
                    $resolved = $configs->where('faculty_id', $student->faculty_id)
                        ->whereNull('department_id')
                        ->whereNull('program_id')
                        ->first();
                }
                if (!$resolved) {
                    $resolved = $configs->whereNull('faculty_id')
                        ->whereNull('department_id')
                        ->whereNull('program_id')
                        ->first();
                }
                if ($resolved) {
                    if ($resolved->feeType && $resolved->feeType->is_one_time) {
                        $alreadyCharged = \App\Models\InvoiceItem::whereHas('invoice', function ($q) use ($student) {
                            $q->where('user_id', $student->user_id);
                        })->where('fee_type_id', $resolved->fee_type_id)->exists();

                        if ($alreadyCharged) {
                            continue;
                        }
                    }
                    $resolvedConfigs->push($resolved);
                }
            }

            $academicTotal = $resolvedConfigs->sum('amount');
            
            $tuition = 0;
            foreach ($resolvedConfigs as $config) {
                if (!$config->feeType || !$config->feeType->is_one_time) {
                    if ($config->feeType && stripos($config->feeType->name, 'tuition') !== false) {
                        $tuition += $config->amount;
                    }
                }
            }

            $discount = 0;
            if ($student->scholarship_id && $student->scholarship) {
                $scholarship = $student->scholarship;
                if ($scholarship->type === 'fixed') {
                    $discount = max(0, $tuition - (float)$scholarship->amount);
                } else {
                    $discountPercent = (float)$scholarship->percentage;
                    $discount = ($tuition * $discountPercent) / 100;
                }
            }

            $amount = max(0, $academicTotal - $discount);
            $description = "School Fees / Tuition for " . $session->name;

             return response()->json([
                'amount' => $amount,
                'description' => $description,
                'breakdown' => [
                    'items' => $resolvedConfigs->map(fn($c) => [
                        'name' => $c->feeType ? $c->feeType->name : 'Fee Item',
                        'amount' => (float)$c->amount,
                    ])->toArray(),
                    'academic_total' => (float)$academicTotal,
                    'scholarship' => $student->scholarship ? [
                        'name' => $student->scholarship->name,
                        'type' => $student->scholarship->type,
                        'percentage' => (float)$student->scholarship->percentage,
                        'amount' => (float)$student->scholarship->amount,
                        'discount' => (float)$discount,
                    ] : null,
                    'total' => $amount,
                ]
            ]);
        }

        if ($validated['type'] === 'hostel') {
            $hostelFee = \App\Models\HostelFee::where('session_id', $session->id)->first();
            $amount = $hostelFee ? (float)$hostelFee->amount : 0.0;
            $description = "Hostel Accommodation Fee for " . $session->name;

            return response()->json([
                'amount' => $amount,
                'description' => $description,
                'breakdown' => [
                    'items' => [
                        [
                            'name' => $hostelFee ? 'Hostel Accommodation Charge' : 'Hostel Fee (Not Configured)',
                            'amount' => $amount,
                        ]
                    ],
                    'academic_total' => $amount,
                    'scholarship' => null,
                    'total' => $amount,
                ]
            ]);
        }

        return response()->json([
            'amount' => 0,
            'description' => '',
            'breakdown' => null,
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('create_invoices') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized. You need the create_invoices permission to generate invoices.');
        }
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
             'amount' => 'required|numeric|min:0',
            'type' => 'required|string',
            'description' => 'required|string|max:255',
            'due_date' => 'required|date',
            'session_id' => 'required|exists:academic_sessions,id',
        ]);

        // Generate reference
        $reference = 'INV-' . strtoupper(uniqid());

        try {
            $invoice = DB::transaction(function () use ($validated, $reference) {
                $invoice = Invoice::create([
                    'user_id' => $validated['user_id'],
                    'session_id' => $validated['session_id'],
                    'reference' => $reference,
                    'type' => $validated['type'],
                    'amount' => $validated['amount'],
                    'due_date' => $validated['due_date'],
                    'status' => 'pending',
                    'paid_amount' => 0,
                    'created_by' => Auth::id(),
                ]);

                if ($validated['type'] === 'school_fee') {
                    $user = \App\Models\User::findOrFail($validated['user_id']);
                    $student = $user->student;
                    $session = \App\Models\Session::findOrFail($validated['session_id']);

                    if ($student) {
                        $targetSessionId = ($student->fee_policy === 'admission_session' && $student->admitted_session_id) 
                            ? $student->admitted_session_id 
                            : $session->id;

                        $allConfigs = \App\Models\FeeConfiguration::where('session_id', $targetSessionId)
                            ->where(function ($q) use ($student) {
                                $q->where('level', $student->current_level)
                                    ->orWhereNull('level');
                            })
                            ->where(function ($q) use ($student) {
                                $q->where('entry_mode', $student->entry_mode)
                                    ->orWhereNull('entry_mode');
                            })
                            ->where('is_compulsory', true)
                            ->with('feeType')
                            ->get();

                        $resolvedConfigs = collect();
                        $groupedConfigs = $allConfigs->groupBy('fee_type_id');

                        foreach ($groupedConfigs as $feeTypeId => $configs) {
                            $resolved = null;
                            if ($student->program_id) {
                                $resolved = $configs->where('program_id', $student->program_id)->first();
                            }
                            if (!$resolved && $student->department_id) {
                                $resolved = $configs->where('department_id', $student->department_id)
                                    ->whereNull('program_id')
                                    ->first();
                            }
                            if (!$resolved && $student->faculty_id) {
                                $resolved = $configs->where('faculty_id', $student->faculty_id)
                                    ->whereNull('department_id')
                                    ->whereNull('program_id')
                                    ->first();
                            }
                            if (!$resolved) {
                                $resolved = $configs->whereNull('faculty_id')
                                    ->whereNull('department_id')
                                    ->whereNull('program_id')
                                    ->first();
                            }
                            if ($resolved) {
                                if ($resolved->feeType && $resolved->feeType->is_one_time) {
                                    $alreadyCharged = \App\Models\InvoiceItem::whereHas('invoice', function ($q) use ($student) {
                                        $q->where('user_id', $student->user_id);
                                    })->where('fee_type_id', $resolved->fee_type_id)->exists();

                                    if ($alreadyCharged) {
                                        continue;
                                    }
                                }
                                $resolvedConfigs->push($resolved);
                            }
                        }

                        $tuition = 0;
                        foreach ($resolvedConfigs as $config) {
                            if (!$config->feeType || !$config->feeType->is_one_time) {
                                if ($config->feeType && stripos($config->feeType->name, 'tuition') !== false) {
                                    $tuition += $config->amount;
                                }
                            }
                        }

                        $discount = 0;
                        $discountDescription = '';
                        if ($student->scholarship_id && $student->scholarship) {
                            $scholarship = $student->scholarship;
                            if ($scholarship->type === 'fixed') {
                                $discount = max(0, $tuition - (float)$scholarship->amount);
                                $discountDescription = "Scholarship Discount (" . $scholarship->name . " - Fixed ₦" . number_format($scholarship->amount, 0) . ")";
                            } else {
                                $discountPercent = (float)$scholarship->percentage;
                                $discount = ($tuition * $discountPercent) / 100;
                                $discountDescription = "Scholarship Discount (" . $scholarship->name . " - " . $scholarship->percentage . "%)";
                            }
                        }

                        // Create items
                        foreach ($resolvedConfigs as $config) {
                            \App\Models\InvoiceItem::create([
                                'invoice_id' => $invoice->id,
                                'fee_type_id' => $config->fee_type_id,
                                'description' => $config->feeType ? $config->feeType->name : 'Fee Item',
                                'amount' => (float)$config->amount,
                            ]);
                        }

                        if ($discount > 0) {
                            \App\Models\InvoiceItem::create([
                                'invoice_id' => $invoice->id,
                                'description' => $discountDescription,
                                'amount' => -$discount,
                            ]);
                        }
                    } else {
                        \App\Models\InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'description' => $validated['description'],
                            'amount' => $validated['amount'],
                        ]);
                    }
                } else {
                    \App\Models\InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $validated['description'],
                        'amount' => $validated['amount'],
                    ]);
                }

                return $invoice;
            });

            return redirect()->route('admin.invoices.show', $invoice->id)->with('success', 'Invoice generated successfully.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Invoice creation failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to create invoice: ' . $e->getMessage())->withErrors(['error' => 'Failed to create invoice: ' . $e->getMessage()]);
        }
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['user.student', 'session', 'items', 'payments.user', 'payments.recorder', 'creator', 'updater']);

        return Inertia::render('Admin/Invoices/Show', [
            'invoice' => $invoice,
            'payments' => $invoice->payments()->with(['user', 'recorder'])->latest()->get(),
        ]);
    }

    public function markAsPaid(Request $request, Invoice $invoice)
    {
        if (!Auth::user()->can('manual_payment_override')) {
            abort(403, 'You do not have permission to manually override payments.');
        }

        $request->validate([
            'amount' => 'nullable|numeric|min:1|max:' . ($invoice->amount - $invoice->paid_amount),
            'paid_at' => 'required|date',
            'channel' => 'required|string|in:transfer,pos,cash,manual',
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
            'channel' => $request->channel,
            'paid_at' => $request->paid_at,
        ]);

        $newTotalPaid = $invoice->paid_amount + $amountToRecord;
        $newStatus = $newTotalPaid >= $invoice->amount ? 'paid' : 'partial';

        $invoice->update([
            'status' => $newStatus,
            'paid_amount' => $newTotalPaid,
            'updated_by' => Auth::id(),
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

            if ($invoice->type === 'school_fee') {
                $student = \App\Models\Student::where('user_id', $invoice->user_id)->first();
                if ($student) {
                    $student->checkAndPromoteStudent();
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

        if (!$paymentData || ($paymentData['status'] ?? null) !== 'success') {
            $statusMsg = $paymentData['status'] ?? 'no response';
            $gatewayResponse = $paymentData['gateway_response'] ?? null;
            if (!$gatewayResponse && isset($paymentData['original_data']['gateway_response'])) {
                $gatewayResponse = $paymentData['original_data']['gateway_response'];
            }
            if ($gatewayResponse) {
                $statusMsg .= " (Reason: {$gatewayResponse})";
            }
            
            // Auto mark the payment as failed
            $payment->update(['status' => 'failed']);
            
            return back()->with('error', "Payment verification failed. Gateway status: {$statusMsg}.");
        }

        if ($payment->status === 'success') {
            return back()->with('info', 'Payment is already marked as successful.');
        }

        return back()->with('success', 'Payment verified and updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        if (!Auth::user()->can('cancel_invoices') && !Auth::user()->can('delete_invoices') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized. You need the cancel_invoices permission to delete invoices.');
        }

        if ($invoice->paid_amount > 0 || $invoice->payments()->where('status', 'success')->count() > 0) {
            return back()->with('error', 'Cannot delete an invoice that has successful payments attached to it.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($invoice) {
            // Delete associated pending payments if any
            $invoice->payments()->delete();

            // Cleanup associated hostel booking if applicable
            \App\Models\HostelBooking::where('invoice_id', $invoice->id)->delete();

            $invoice->items()->delete();
            $invoice->delete();
        });

        return redirect()->route('admin.invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    public function updateItems(Request $request, Invoice $invoice)
    {
        if (!Auth::user()->can('edit_invoices') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized. You need the edit_invoices permission to edit invoice items.');
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric',
        ]);

        DB::transaction(function () use ($request, $invoice) {
            // Replace existing items
            $invoice->items()->delete();

            $totalAmount = 0;
            foreach ($request->items as $itemData) {
                $amount = (float) $itemData['amount'];
                $totalAmount += $amount;

                \App\Models\InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $itemData['description'],
                    'amount' => $amount,
                ]);
            }

            $newTotalAmount = max(0, $totalAmount);
            $paidAmount = (float) $invoice->paid_amount;

            $newStatus = 'pending';
            if ($paidAmount >= $newTotalAmount && $newTotalAmount > 0) {
                $newStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $newStatus = 'partial';
            }

            $invoice->update([
                'amount' => $newTotalAmount,
                'status' => $newStatus,
                'updated_by' => Auth::id(),
            ]);
        });

        return back()->with('success', 'Invoice breakdown items and total updated successfully.');
    }

    public function recalculate(Invoice $invoice)
    {
        if (!Auth::user()->can('edit_invoices') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized. You need the edit_invoices permission to recalculate invoices.');
        }

        if ($invoice->type !== 'school_fee') {
            return back()->with('error', 'Automated fee recalculation is currently supported for school fee invoices.');
        }

        $student = \App\Models\Student::with(['scholarship', 'program'])->where('user_id', $invoice->user_id)->first();
        if (!$student || !$invoice->session) {
            return back()->with('error', 'Student profile or academic session record missing for invoice.');
        }

        $feeService = app(\App\Services\Finance\FeeService::class);
        $expectedAmount = $feeService->calculateExpectedSchoolFee($student, $invoice->session);

        $currentItemsTotal = (float) $invoice->items()->sum('amount');
        $discrepancy = $expectedAmount - $currentItemsTotal;

        if (abs($discrepancy) < 0.01) {
            return back()->with('info', 'Invoice amount is already perfectly aligned with current fee configurations.');
        }

        DB::transaction(function () use ($invoice, $discrepancy, $expectedAmount) {
            $desc = $discrepancy > 0 
                ? 'Fee Recalibration Adjustment (Supplementary Charge)'
                : 'Fee Recalibration Adjustment (Credit Adjustment)';

            \App\Models\InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $desc,
                'amount' => $discrepancy,
            ]);

            $newTotalAmount = max(0, $expectedAmount);
            $paidAmount = (float) $invoice->paid_amount;

            $newStatus = 'pending';
            if ($paidAmount >= $newTotalAmount && $newTotalAmount > 0) {
                $newStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $newStatus = 'partial';
            }

            $invoice->update([
                'amount' => $newTotalAmount,
                'status' => $newStatus,
                'updated_by' => Auth::id(),
            ]);
        });

        $adjustedFormatted = number_format(abs($discrepancy), 2);
        $typeWord = $discrepancy > 0 ? 'added' : 'credited';
        return back()->with('success', "Invoice recalculated successfully. Adjustment of ₦{$adjustedFormatted} {$typeWord}.");
    }
}
