<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StudentSession;
use App\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PaymentController extends Controller
{
    protected $gateway;
    
    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    public function index()
    {
        $invoices = Invoice::where('user_id', Auth::id())
            ->with([
                'items',
                'session',
                'payments' => function ($query) {
                    $query->where('status', 'success');
                },
            ])
            ->latest()
            ->get();

        $currentSession = \App\Models\Session::current();
        $canGenerateInvoice = false;

        if ($currentSession) {
            $hasInvoice = Invoice::where('user_id', Auth::id())
                ->where('type', 'school_fee')
                ->where('session_id', $currentSession->id)
                ->exists();

            $canGenerateInvoice = !$hasInvoice;
        }

        return Inertia::render('Student/Finance/Index', [
            'invoices' => $invoices,
            'canGenerateInvoice' => $canGenerateInvoice,
        ]);
    }

    public function pay(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice already paid.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $balance = $invoice->amount - $invoice->paid_amount;
        $amountToPay = (float) $request->input('amount');

        // Strict Validation Rules
        $isFullPayment = abs($amountToPay - $balance) < 1; // Allow small float diff

        $halfAmount = $invoice->amount / 2;
        $isHalfPayment = abs($amountToPay - $halfAmount) < 1;

        if ($invoice->paid_amount == 0) {
            // First payment: Can be Full or Half
            if (!$isFullPayment && !$isHalfPayment) {
                return back()->with('error', 'You can only pay the full amount (' . number_format($balance) . ') or a 50% installment (' . number_format($halfAmount) . ').');
            }
        } else {
            // Subsequent payments: Must be Full Balance
            if (!$isFullPayment) {
                return back()->with('error', 'You must pay the remaining balance of ' . number_format($balance, 2));
            }
        }

        if ($amountToPay > $balance) {
            return back()->with('error', 'Amount exceeds remaining balance.');
        }

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => Auth::id(),
            'gateway_reference' => 'TEMP-' . uniqid(), // Temporary ref
            'amount' => $amountToPay,
            'status' => 'pending',
        ]);

        // We actually use the Paystack Reference as gateway_reference if initializing.
        // Paystack generates one or acts on ours.
        // Let's generate ours: "PAY-" . uniqid()
        $reference = 'PAY-' . strtoupper(uniqid());
        $payment->update(['gateway_reference' => $reference]);

        $data = $this->gateway->initializeTransaction(
            Auth::user()->email,
            $amountToPay,
            $reference,
            route('student.payments.callback'),
            [
                'customer_name' => Auth::user()->name,
                'payment_type' => $invoice->type,
                'invoice_id' => $invoice->id,
            ]
        );

        if ($data && isset($data['authorization_url'])) {
            return Inertia::location($data['authorization_url']);
        }

        return back()->with('error', 'Payment initialization failed.');
    }

    public function callback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('transaction_ref');
        if (!$reference) {
            return redirect()->route('student.payments.index')->with('error', 'No reference supplied.');
        }

        $data = $this->gateway->verifyTransaction($reference);

        if ($data && $data['status'] === 'success') {
            $payment = Payment::where('gateway_reference', $reference)->first();

            if ($payment && $payment->status !== 'success') {
                app(\App\Services\Payment\PaymentHandler::class)->handleSuccessfulPayment($reference, $data);
            }

            return redirect()->route('student.payments.index')->with('success', 'Payment successful!');
        }

        return redirect()->route('student.payments.index')->with('error', 'Payment verification failed.');
    }

    public function createSchoolFeeInvoice()
    {
        $user = Auth::user();
        $student = \App\Models\Student::with(['scholarship', 'admittedSession'])->where('user_id', $user->id)->firstOrFail();

        // Check for pending SCHOOL FEE invoice
        $pendingInvoice = Invoice::where('user_id', $user->id)
            ->where('type', 'school_fee')
            ->where('status', 'pending')
            ->first();

        if ($pendingInvoice) {
            return redirect()->route('student.payments.index')->with('info', 'You already have a pending invoice.');
        }

        // Get current system session
        $currentSession = \App\Models\Session::current();
        if (!$currentSession) {
            return back()->with('error', 'No active academic session found.');
        }

        // Determine which session to use for fee lookup based on policy
        // If policy is admission_session, we use their original session fees.
        $targetSessionId = ($student->fee_policy === 'admission_session' && $student->admitted_session_id) 
            ? $student->admitted_session_id 
            : $currentSession->id;

        // Fetch ALL potentially applicable fees for the target session
        $allConfigs = \App\Models\FeeConfiguration::where('session_id', $targetSessionId)
            ->where(function ($q) use ($student) {
                $q->where('level', $student->current_level)
                    ->orWhereNull('level');
            })
            ->with('feeType')
            ->get();

        // Specificity Resolver: Group by FeeType and pick the most specific one
        $resolvedConfigs = collect();
        $groupedConfigs = $allConfigs->groupBy('fee_type_id');

        foreach ($groupedConfigs as $feeTypeId => $configs) {
            $resolved = null;

            // 1. Check for Program match
            if ($student->program_id) {
                $resolved = $configs->where('program_id', $student->program_id)->first();
            }

            // 2. Check for Department match
            if (!$resolved && $student->department_id) {
                $resolved = $configs->where('department_id', $student->department_id)
                    ->whereNull('program_id')
                    ->first();
            }

            // 3. Check for Faculty match
            if (!$resolved && $student->faculty_id) {
                $resolved = $configs->where('faculty_id', $student->faculty_id)
                    ->whereNull('department_id')
                    ->whereNull('program_id')
                    ->first();
            }

            // 4. Fallback to Global (no faculty/dept/program)
            if (!$resolved) {
                $resolved = $configs->whereNull('faculty_id')
                    ->whereNull('department_id')
                    ->whereNull('program_id')
                    ->first();
            }

            if ($resolved) {
                $resolvedConfigs->push($resolved);
            }
        }

        if ($resolvedConfigs->isEmpty()) {
            return back()->with('error', 'No fee configuration found for your profile. Please contact support.');
        }

        $totalAmount = $resolvedConfigs->sum('amount');
        
        // Add Global Admin Charge if enabled
        $adminChargeEnabled = \App\Models\SystemSetting::get('admin_charge_enabled', true);
        $adminChargeAmount = \App\Models\SystemSetting::get('admin_charge_amount', 250000);
        
        if ($adminChargeEnabled) {
            $totalAmount += $adminChargeAmount;
        }

        // Apply Scholarship Discount
        $discountAmount = 0;
        if ($student->scholarship && ($student->program?->scholarship_eligible ?? true)) {
            $discountAmount = $totalAmount * ($student->scholarship->percentage / 100);
        }

        $finalAmount = $totalAmount - $discountAmount;

        // Find or Create StudentSession
        $studentSession = StudentSession::firstOrCreate(
            [
                'student_id' => $student->id,
                'session_id' => $currentSession->id,
            ],
            [
                'level' => $student->current_level,
                'status' => 'active',
            ]
        );

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'session_id' => $currentSession->id,
            'student_session_id' => $studentSession->id,
            'type' => 'school_fee',
            'reference' => 'SCH-' . strtoupper(uniqid()),
            'amount' => $finalAmount,
            'status' => 'pending',
            'due_date' => now()->addWeeks(4),
        ]);

        // Create Invoice Items
        foreach ($resolvedConfigs as $config) {
            \App\Models\InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'fee_type_id' => $config->fee_type_id,
                'description' => $config->feeType->name ?? 'Fee',
                'amount' => $config->amount,
            ]);
        }
        
        // Add Admin Charge item
        if ($adminChargeEnabled) {
            \App\Models\InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'fee_type_id' => null, // Or specific fee type if exists
                'description' => 'Administrative Charges',
                'amount' => $adminChargeAmount,
            ]);
        }

        // Add discount item if applicable
        if ($discountAmount > 0) {
            \App\Models\InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'fee_type_id' => null,
                'description' => 'Scholarship Discount (' . $student->scholarship->name . ' - ' . floatval($student->scholarship->percentage) . '%)',
                'amount' => -$discountAmount,
            ]);
        }

        return redirect()->route('student.payments.index')->with('success', 'School Fee invoice generated successfully.');
    }
}
