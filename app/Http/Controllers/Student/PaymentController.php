<?php

namespace App\Http\Controllers\Student;

use App\Contracts\PaymentGatewayInterface;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Session;
use App\Services\Finance\FeeService;
use App\Services\Payment\PaymentHandler;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PaymentController extends Controller
{
    protected $gateway;

    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    private function getGatewayByName($name)
    {
        if ($name === 'squadco') {
            return new \App\Services\SquadcoService;
        }

        return new \App\Services\PaystackService;
    }

    public function downloadReceipt(Payment $payment)
    {
        if ($payment->user_id !== Auth::id()) {
            abort(403);
        }

        if ($payment->status !== 'success') {
            return back()->with('error', 'Only successful payments have receipts.');
        }

        $payment->load(['invoice.session', 'user.student']);

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

    public function index()
    {
        $feeService = app(FeeService::class);
        $rawInvoices = Invoice::where('user_id', Auth::id())
            ->where('status', '!=', 'paid')
            ->where('type', 'school_fee')
            ->get();

        foreach ($rawInvoices as $invoice) {
            $feeService->refreshInvoiceIfUnpaid($invoice);
        }

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

        $currentSession = Session::current();
        $canGenerateInvoice = false;
        $optionalFees = [];

        $student = Auth::user()->student;
        if ($currentSession && $student) {
            $canGenerateInvoice = $currentSession->school_fee_payment_enabled && 
                ! Invoice::where('user_id', Auth::id())
                    ->where('type', 'school_fee')
                    ->where('session_id', $currentSession->id)
                    ->exists();

            $optionalFees = $feeService->getAvailableOptionalFees($student, $currentSession);
        }

        return Inertia::render('Student/Finance/Index', [
            'invoices' => $invoices,
            'canGenerateInvoice' => $canGenerateInvoice,
            'optionalFees' => $optionalFees,
            'admin_charge_splittable' => (bool) \App\Models\SystemSetting::get('admin_charge_splittable', true),
        ]);
    }

    public function getOptionalFees()
    {
        $student = Auth::user()->student;
        $currentSession = Session::current();
        if (! $student || ! $currentSession) {
            return response()->json([]);
        }

        $feeService = app(FeeService::class);
        $optionalFees = $feeService->getAvailableOptionalFees($student, $currentSession);

        return response()->json($optionalFees);
    }

    public function initiateOptionalFee(\App\Models\FeeConfiguration $config)
    {
        $student = Auth::user()->student;
        $currentSession = Session::current();

        if (! $student || ! $student->department_id) {
            return back()->with('error', 'You cannot generate optional fee invoices because your academic department has not been assigned.');
        }

        if (! $currentSession) {
            return back()->with('error', 'Student profile or active session not found.');
        }

        if ($config->session_id !== $currentSession->id) {
            return back()->with('error', 'Invalid session fee configuration.');
        }

        $feeService = app(FeeService::class);
        $invoice = $feeService->generateOptionalFeeInvoice($student, $currentSession, $config);

        if (! $invoice) {
            return back()->with('error', 'Failed to generate invoice. It may have already been generated or paid.');
        }

        return redirect()->route('student.payments.index')->with('success', 'Optional Fee invoice generated successfully.');
    }

    public function pay(Request $request, Invoice $invoice)
    {
        $student = Auth::user()->student;
        if (! $student || ! $student->department_id) {
            return back()->with('error', 'You cannot proceed with payment because your academic department has not been assigned to your profile. Please contact the Bursary / Student Affairs office.');
        }

        // Auto-refresh invoice if unpaid before proceeding
        $feeService = app(FeeService::class);
        $invoice = $feeService->refreshInvoiceIfUnpaid($invoice);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice already paid.');
        }

        if ($invoice->type === 'school_fee') {
            $session = $invoice->session;
            if ($session && !$session->school_fee_payment_enabled) {
                return back()->with('error', 'School fee payments are currently disabled for the ' . $session->name . ' session.');
            }
        }

        $balance = (float) $invoice->amount - (float) $invoice->paid_amount;

        if ($balance <= 0.01) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
                'transaction_id' => 'SCHOLARSHIP'.date('Y').strtoupper(Str::random(8)),
                'gateway' => 'scholarship',
                'gateway_reference' => 'SCH-'.strtoupper(uniqid()),
                'amount' => 0,
                'status' => 'pending',
            ]);

            app(PaymentHandler::class)->handleSuccessfulPayment($payment->gateway_reference, [
                'channel' => 'scholarship',
                'id' => $payment->transaction_id,
            ]);

            return redirect()->route('student.payments.index')->with('success', 'Zero-fee scholarship invoice marked as paid.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $balance = (float) $invoice->amount - (float) $invoice->paid_amount;
        $amountToPay = (float) $request->input('amount');

        $isFullPayment = abs($amountToPay - $balance) < 0.01;

        // Disallow split payments for non-school and non-hostel fees (e.g. acceptance_fee, other_fee, application_fee)
        if ($invoice->type !== 'school_fee' && $invoice->type !== 'hostel_fee') {
            if (! $isFullPayment) {
                return back()->with('error', 'Split payments are not supported for this type of fee. The full remaining balance of '.number_format($balance, 2).' NGN must be paid.');
            }
        }

        // Enforce specific installment split rules for hostel fee (first payment >= 75%, second payment clears balance)
        if ($invoice->type === 'hostel_fee') {
            if ($invoice->paid_amount <= 0.01) {
                // First payment: must be >= 75% of total amount
                $minFirstPayment = (float) $invoice->amount * 0.75;
                if ($amountToPay < $minFirstPayment) {
                    return back()->with('error', 'Your first payment for the hostel fee must be at least 75% of the total amount (Minimum: '.number_format($minFirstPayment, 2).' NGN).');
                }
            } else {
                // Subsequent payment: must clear the remaining balance in full
                if (! $isFullPayment) {
                    return back()->with('error', 'The remaining balance of '.number_format($balance, 2).' NGN for the hostel fee must be paid in full.');
                }
            }
        }

        // Calculate Minimum Required Upfront Payment based on Splittability Rules
        $adminChargeSplittable = \App\Models\SystemSetting::get('admin_charge_splittable', true);
        $adminChargeItemAmount = (float) $invoice->items()->where('description', 'Administrative Charges')->sum('amount');
        $netAcademicPortion = (float) $invoice->amount - $adminChargeItemAmount;

        $minUpfront = (float) $invoice->amount / 2; // Default 50%
        if ($invoice->type === 'hostel_fee') {
            $minUpfront = (float) $invoice->amount * 0.75;
        } elseif (! $adminChargeSplittable && $adminChargeItemAmount > 0) {
            // Admin must be paid full, academic can be split
            $minUpfront = ($netAcademicPortion / 2) + $adminChargeItemAmount;
        }

        // If academic fees are 0 (e.g. 100% scholarship), then min payment is the full balance
        if ($netAcademicPortion <= 0) {
            $minUpfront = (float) $invoice->amount;
        }

        // Flexible Validation Rules
        $isFullPayment = abs($amountToPay - $balance) < 0.01;
        $totalPaidIfSuccessful = (float) $invoice->paid_amount + $amountToPay;

        if (! $isFullPayment) {
            if ($totalPaidIfSuccessful < $minUpfront) {
                return back()->with('error', 'Minimum required upfront payment is '.number_format($minUpfront).'. You have only paid '.number_format($invoice->paid_amount).'.');
            }

            // Optional: Prevent extremely small payments (e.g. less than 1000)
            if ($amountToPay < 1000) {
                return back()->with('error', 'The minimum payment amount allowed is 1,000 NGN.');
            }
        }

        if ($amountToPay > ($balance + 0.01)) {
            return back()->with('error', 'Amount exceeds remaining balance of '.number_format($balance, 2));
        }

        // Check for the last pending payment and verify its status before proceeding
        $lastPending = Payment::where('invoice_id', $invoice->id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($lastPending && ! str_starts_with($lastPending->gateway_reference, 'TEMP-')) {
            // Verify using the gateway that was actually used for this payment
            $checkGateway = $this->getGatewayByName($lastPending->gateway ?? 'squadco');
            $verification = $checkGateway->verifyTransaction($lastPending->gateway_reference);

            if ($verification && $verification['status'] === 'success') {
                app(PaymentHandler::class)->handleSuccessfulPayment($lastPending->gateway_reference, $verification);

                return Inertia::render('Student/Finance/Success', [
                    'payment' => $lastPending->fresh(),
                    'invoice' => $invoice,
                ]);
            } else {
                // If abandoned, failed, cancelled, expired, or non-successful, mark as failed so student can retry cleanly
                $lastPending->update(['status' => 'failed']);
            }
        }

        $activeGateway = \App\Models\SystemSetting::get('payment_gateway', env('PAYMENT_GATEWAY', 'squadco'));

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => Auth::id(),
            'transaction_id' => 'MIUPAY'.date('Y').strtoupper(Str::random(8)),
            'gateway' => $activeGateway,
            'gateway_reference' => 'TEMP-'.uniqid(), // Temporary ref
            'amount' => $amountToPay,
            'status' => 'pending',
        ]);

        // We actually use the Paystack Reference as gateway_reference if initializing.
        // Paystack generates one or acts on ours.
        // Let's generate ours: "PAY-" . uniqid()
        $reference = 'PAY-'.strtoupper(uniqid());
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
        if (! $reference) {
            return Inertia::render('Student/Finance/Failure', [
                'error' => 'No transaction reference was provided by the payment gateway.',
            ]);
        }

        $data = $this->gateway->verifyTransaction($reference);
        $payment = Payment::where('gateway_reference', $reference)->first();

        if ($data && $data['status'] === 'success') {
            if ($payment) {
                if ($payment->status !== 'success') {
                    app(PaymentHandler::class)->handleSuccessfulPayment($reference, $data);
                }

                return Inertia::render('Student/Finance/Success', [
                    'payment' => $payment,
                    'invoice' => $payment->invoice,
                ]);
            }

            return redirect()->route('student.payments.index')->with('success', 'Payment successful!');
        }

        if ($payment) {
            $payment->update(['status' => 'failed']);
        }

        return Inertia::render('Student/Finance/Failure', [
            'error' => $data['message'] ?? 'The payment gateway could not verify this transaction.',
            'reference' => $reference,
        ]);
    }

    public function createSchoolFeeInvoice()
    {
        $user = Auth::user();
        $student = \App\Models\Student::where('user_id', $user->id)->firstOrFail();
        $currentSession = Session::current();

        if (! $currentSession) {
            return back()->with('error', 'No active academic session found.');
        }

        $feeService = app(FeeService::class);
        $invoice = $feeService->generateSchoolFeeInvoice($student, $currentSession);

        if (! $invoice) {
            return back()->with('error', 'No fee configuration found for your profile. Please contact support.');
        }

        return redirect()->route('student.payments.index')->with('success', 'School Fee invoice generated successfully.');
    }
}
