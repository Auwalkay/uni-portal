<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Applicant;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paystack;

    public function __construct(PaystackService $paystack)
    {
        $this->paystack = $paystack;
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

        $data = $this->paystack->initializeTransaction(
            $user->email,
            $invoice->amount,
            $reference,
            route('applicant.payment.callback')
        );

        if ($data && isset($data['authorization_url'])) {
            return Inertia::location($data['authorization_url']);
        }

        return back()->with('error', 'Payment initialization failed.');
    }

    public function callback(Request $request)
    {
        $reference = $request->query('reference');
        if (!$reference) {
            return redirect()->route('applicant.payment.index')->with('error', 'No reference supplied.');
        }

        $data = $this->paystack->verifyTransaction($reference);

        if ($data && $data['status'] === 'success') {
            $payment = Payment::where('gateway_reference', $reference)->first();

            if ($payment && $payment->status !== 'success') {
                $payment->update([
                    'status' => 'success',
                    'channel' => $data['channel'],
                    'paid_at' => now(),
                    'amount' => $data['amount'] / 100, // Paystack returns kobo
                ]);

                // Update Invoice
                $invoice = $payment->invoice;
                $invoice->update([
                    'status' => 'paid',
                    'paid_amount' => $invoice->amount
                ]);

                // Finalize Application
                $applicant = Applicant::where('user_id', $payment->user_id)->first();
                if ($applicant && $applicant->status === 'pending_payment') {
                    $applicant->update([
                        'status' => 'submitted',
                        'application_number' => \App\Helpers\ApplicationNumberHelper::generate(),
                    ]);

                    // Notify User
                    $payment->user->notify(new \App\Notifications\ApplicationSubmitted($applicant));
                }
            }

            return redirect()->route('applicant.apply.show')->with('success', 'Payment successful! Application submitted.');
        }

        return redirect()->route('applicant.payment.index')->with('error', 'Payment verification failed.');
    }
}
