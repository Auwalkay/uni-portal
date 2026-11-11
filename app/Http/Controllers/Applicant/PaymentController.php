<?php

namespace App\Http\Controllers\Applicant;

use App\Contracts\PaymentGatewayInterface;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\SystemSetting;
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
        $user = Auth::user();
        $invoice = Invoice::with('session')
            ->where('user_id', $user->id)
            ->where('type', 'application_fee')
            ->where('status', '!=', 'paid')
            ->latest()
            ->first();

        // If no pending invoice, maybe they already paid or need one generated?
        if (! $invoice) {
            $applicant = Applicant::where('user_id', $user->id)->first();

            // Check if they have a paid one
            $paidInvoice = Invoice::where('user_id', $user->id)
                ->where('type', 'application_fee')
                ->where('status', 'paid')
                ->exists();

            if ($paidInvoice) {
                if ($applicant && in_array($applicant->status, ['submitted', 'screening', 'admitted', 'enrolled'])) {
                    return redirect()->route('applicant.apply.show')->with('info', 'Your application fee has already been paid.');
                }

                return redirect()->route('applicant.apply.form')->with('info', 'Your application fee has already been paid. Please complete and submit your application form.');
            }

            if (! $applicant || ! $applicant->program_choice_1) {
                return redirect()->route('applicant.apply.start')->with('warning', 'Please select your preferred programme of study first.');
            }

            $currentSession = \App\Services\AcademicCacheService::getCurrentSession();
            if (! $currentSession) {
                return redirect()->route('applicant.dashboard')->with('error', 'No active academic session found for payment.');
            }

            $appFee = $applicant->isPostgraduate()
                ? SystemSetting::get('pg_application_fee', 50000)
                : SystemSetting::get('application_fee', 100000);

            $invoice = Invoice::create([
                'user_id' => $user->id,
                'type' => 'application_fee',
                'session_id' => $currentSession->id,
                'reference' => 'APP-'.strtoupper(uniqid()),
                'amount' => $appFee,
                'paid_amount' => 0,
                'status' => 'pending',
                'due_date' => now()->addDays(7),
            ]);

            $invoice->items()->create([
                'description' => $applicant->isPostgraduate() ? 'Postgraduate Application Form' : 'Application Fee',
                'amount' => $appFee,
                'quantity' => 1,
            ]);

            $invoice->load('session');
        }

        $currentSession = \App\Services\AcademicCacheService::getCurrentSession();

        // Ensure pending invoice is associated with current session if session_id is missing
        if (! $invoice->session_id && $currentSession) {
            $invoice->update(['session_id' => $currentSession->id]);
            $invoice->setRelation('session', $currentSession);
        }

        $applicant = Applicant::with(['programme', 'programme.department', 'user'])->where('user_id', $user->id)->first();

        return Inertia::render('Applicant/Payment/Index', [
            'invoice' => $invoice->load(['session', 'items']),
            'currentSession' => $currentSession,
            'applicant' => $applicant,
            'programme' => $applicant?->programme,
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

        if ($initiation && ! empty($initiation['authorization_url'])) {
            return Inertia::location($initiation['authorization_url']);
        }

        return back()->with('error', 'Payment initialization failed.');
    }

    public function callback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('transaction_ref');
        if (! $reference) {
            return redirect()->route('applicant.payment.index')->with('error', 'No reference supplied.');
        }

        $result = app(\App\Services\Payment\PaymentHandler::class)->verifyAndProcessPayment($reference);

        if ($result['status'] === 'success') {
            $user = $request->user();
            $applicant = Applicant::where('user_id', $user?->id)->first();
            if ($applicant && in_array($applicant->status, ['submitted', 'screening', 'admitted', 'enrolled'])) {
                return redirect()->route('applicant.apply.show')->with('success', 'Payment successful! Application submitted.');
            }

            return redirect()->route('applicant.apply.form')->with('success', 'Payment confirmed! Please complete and submit your application details.');
        }

        return redirect()->route('applicant.payment.index')->with('error', 'Payment verification failed or is pending confirmation.');
    }
}
