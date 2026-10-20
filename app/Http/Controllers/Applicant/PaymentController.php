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
        $invoice = Invoice::with('session')
            ->where('user_id', $user->id)
            ->where('type', 'application_fee')
            ->where('status', '!=', 'paid')
            ->latest()
            ->first();

        // If no pending invoice, maybe they already paid or need one generated?
        if (!$invoice) {
            // Check if they have a paid one
            $paidInvoice = Invoice::where('user_id', $user->id)
                ->where('type', 'application_fee')
                ->where('status', 'paid')
                ->exists();

            if ($paidInvoice) {
                return redirect()->route('applicant.apply.show')->with('info', 'Your application fee has already been paid.');
            }

            // If an applicant record exists, ensure an application fee invoice is ready for payment
            $applicant = Applicant::where('user_id', $user->id)->first();
            if ($applicant) {
                $currentSession = \App\Services\AcademicCacheService::getCurrentSession();
                if (!$currentSession) {
                    return redirect()->route('applicant.dashboard')->with('error', 'No active academic session found for payment.');
                }

                $invoice = Invoice::create([
                    'user_id' => $user->id,
                    'type' => 'application_fee',
                    'session_id' => $currentSession->id,
                    'reference' => 'APP-' . strtoupper(uniqid()),
                    'amount' => \App\Models\SystemSetting::get('application_fee', 100000),
                    'paid_amount' => 0,
                    'status' => 'pending',
                    'due_date' => now()->addDays(7),
                ]);

                $invoice->items()->create([
                    'description' => 'Application Fee',
                    'amount' => \App\Models\SystemSetting::get('application_fee', 100000),
                    'quantity' => 1,
                ]);

                $invoice->load('session');
            } else {
                return redirect()->route('applicant.apply.start')->with('info', 'Please begin your application first.');
            }
        }

        $currentSession = \App\Services\AcademicCacheService::getCurrentSession();

        // Ensure pending invoice is associated with current session if session_id is missing
        if (!$invoice->session_id && $currentSession) {
            $invoice->update(['session_id' => $currentSession->id]);
            $invoice->setRelation('session', $currentSession);
        }

        return Inertia::render('Applicant/Payment/Index', [
            'invoice' => $invoice,
            'currentSession' => $currentSession,
        ]);
    }

    public function pay(Request $request)
    {
        $request->validate([
            'agreed_to_policy' => 'required|accepted',
        ], [
            'agreed_to_policy.accepted' => 'You must read and agree to the Non-Refundable Payment Policy before proceeding.',
            'agreed_to_policy.required' => 'You must read and agree to the Non-Refundable Payment Policy before proceeding.',
        ]);

        $user = Auth::user();
        $invoice = Invoice::where('user_id', $user->id)
            ->where('type', 'application_fee')
            ->where('status', '!=', 'paid')
            ->latest()
            ->firstOrFail();

        $paymentHandler = app(\App\Services\Payment\PaymentHandler::class);
        $initiation = $paymentHandler->initiatePayment(
            $invoice,
            $user,
            (float) $invoice->amount,
            route('applicant.payment.callback')
        );

        if ($initiation && !empty($initiation['authorization_url'])) {
            return Inertia::location($initiation['authorization_url']);
        }

        return back()->with('error', 'Payment initialization failed.');
    }

    public function callback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('transaction_ref');
        if (!$reference) {
            return redirect()->route('applicant.payment.index')->with('error', 'No reference supplied.');
        }

        $result = app(\App\Services\Payment\PaymentHandler::class)->verifyAndProcessPayment($reference);

        if ($result['status'] === 'success') {
            return redirect()->route('applicant.apply.show')->with('success', 'Payment successful! Application submitted.');
        }

        return redirect()->route('applicant.payment.index')->with('error', 'Payment verification failed or is pending confirmation.');
    }
}
