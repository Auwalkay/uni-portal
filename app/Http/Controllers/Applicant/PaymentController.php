<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Applicant;
use App\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $gateway;

    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    public function index()
    {
        $user = Auth::user();
        $invoice = Invoice::where('user_id', $user->id)
            ->where('type', 'application_fee')
            ->where('status', '!=', 'paid')
            ->latest()
            ->first();

        // If no pending invoice, maybe they already paid?
        if (!$invoice) {
            // Check if they have a paid one
            $paidInvoice = Invoice::where('user_id', $user->id)
                ->where('type', 'application_fee')
                ->where('status', 'paid')
                ->exists();

            if ($paidInvoice) {
                return redirect()->route('applicant.apply.show');
            }

            return redirect()->route('applicant.dashboard')->with('error', 'No application invoice found.');
        }

        return Inertia::render('Applicant/Payment/Index', [
            'invoice' => $invoice
        ]);
    }

    public function pay(Request $request)
    {
        $user = Auth::user();
        $invoice = Invoice::where('user_id', $user->id)
            ->where('type', 'application_fee')
            ->where('status', '!=', 'paid')
            ->firstOrFail();

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'gateway_reference' => 'TEMP-' . uniqid(),
            'amount' => $invoice->amount,
            'status' => 'pending',
        ]);

        $reference = 'PAY-' . strtoupper(uniqid());
        $payment->update(['gateway_reference' => $reference]);

        $data = $this->gateway->initializeTransaction(
            $user->email,
            $invoice->amount,
            $reference,
            route('applicant.payment.callback'),
            [
                'customer_name' => $user->name,
                'payment_type' => 'application_fee',
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
            return redirect()->route('applicant.payment.index')->with('error', 'No reference supplied.');
        }

        $data = $this->gateway->verifyTransaction($reference);

        if ($data && $data['status'] === 'success') {
            $payment = Payment::where('gateway_reference', $reference)->first();

            if ($payment && $payment->status !== 'success') {
                app(\App\Services\Payment\PaymentHandler::class)->handleSuccessfulPayment($reference, $data);
            }

            return redirect()->route('applicant.apply.show')->with('success', 'Payment successful! Application submitted.');
        }

        return redirect()->route('applicant.payment.index')->with('error', 'Payment verification failed.');
    }
}
