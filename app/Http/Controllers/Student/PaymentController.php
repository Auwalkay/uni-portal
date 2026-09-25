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

    private function resolveGatewayForInvoice(?Invoice $invoice, ?string $fallbackGateway = null): array
    {
        return \App\Services\Payment\PaymentGatewayFactory::resolveWithGatewayName($invoice, $fallbackGateway);
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

        if (! $student || ! $student->hasDepartment()) {
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
        if (! $student || ! $student->hasDepartment()) {
            return back()->with('error', 'You cannot proceed with payment because your academic department has not been assigned to your profile. Please contact the Bursary / Student Affairs office.');
        }

        // Auto-refresh invoice if unpaid before proceeding
        $feeService = app(FeeService::class);
        $invoice = $feeService->refreshInvoiceIfUnpaid($invoice);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice already paid.');
        }

        if ($invoice->status === 'cancelled') {
            return back()->with('error', 'This invoice has been cancelled. Please generate or select a new reservation/invoice.');
        }

        // Strict Expiry Check: Only 0% paid non-school-fee pending invoices can expire
        $isSchoolFee = ($invoice->type === 'school_fee');
        $hasPartialPayment = ($invoice->status === 'partial' || (float) $invoice->paid_amount > 0);

        if (! $isSchoolFee && ! $hasPartialPayment && $invoice->due_date && $invoice->due_date->isPast()) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($invoice) {
                $invoice->update(['status' => 'cancelled']);
                if ($invoice->booking && $invoice->booking->status === 'pending') {
                    $invoice->booking->update(['status' => 'cancelled']);
                }
            });

            return back()->with('error', 'This initial reservation invoice has expired and cannot be paid. Please generate or select a new room/reservation.');
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

        $balance = max(0, (float) $invoice->amount - (float) $invoice->paid_amount);
        if ($balance <= 0.01) {
            return back()->with('error', 'This invoice is already fully paid.');
        }

        $amountToPay = (float) $request->input('amount');
        $isFullPayment = abs($amountToPay - $balance) < 0.01;

        // Disallow split payments for non-school and non-hostel fees (e.g. acceptance_fee, other_fee, application_fee)
        if ($invoice->type !== 'school_fee' && $invoice->type !== 'hostel_fee' && $invoice->type !== 'hostel') {
            if (! $isFullPayment) {
                return back()->with('error', 'Split payments are not supported for this type of fee. The full remaining balance of '.number_format($balance, 2).' NGN must be paid.');
            }
        }

        // Enforce specific installment split rules for hostel fee (first payment >= 75%, second payment clears balance)
        if ($invoice->type === 'hostel_fee' || $invoice->type === 'hostel') {
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
        if ($invoice->type === 'hostel_fee' || $invoice->type === 'hostel') {
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

        $paymentHandler = app(PaymentHandler::class);

        // Check for the last pending payment and verify its status before proceeding
        $lastPending = Payment::where('invoice_id', $invoice->id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($lastPending && ! str_starts_with($lastPending->gateway_reference, 'TEMP-')) {
            $verificationResult = $paymentHandler->verifyAndProcessPayment($lastPending->gateway_reference, $invoice, $lastPending->gateway);

            if ($verificationResult['status'] === 'success') {
                return Inertia::render('Student/Finance/Success', [
                    'payment' => $verificationResult['payment'],
                    'invoice' => $invoice,
                ]);
            }
        }

        $initiation = $paymentHandler->initiatePayment(
            $invoice,
            Auth::user(),
            $amountToPay,
            route('student.payments.callback')
        );

        if ($initiation && ! empty($initiation['authorization_url'])) {
            return Inertia::location($initiation['authorization_url']);
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

        $result = app(PaymentHandler::class)->verifyAndProcessPayment($reference);

        if ($result['status'] === 'success') {
            if ($result['payment']) {
                return Inertia::render('Student/Finance/Success', [
                    'payment' => $result['payment'],
                    'invoice' => $result['payment']->invoice,
                ]);
            }

            return redirect()->route('student.payments.index')->with('success', 'Payment successful!');
        }

        $data = $result['data'] ?? [];

        return Inertia::render('Student/Finance/Failure', [
            'error' => $data['gateway_response'] ?? $data['message'] ?? 'The payment gateway could not verify this transaction at this time.',
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
