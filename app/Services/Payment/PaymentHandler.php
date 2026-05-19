<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Applicant;
use App\Services\EnrollmentService;
use App\Mail\FeeReceipt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PaymentHandler
{
    public function handleSuccessfulPayment($reference, $data)
    {
        $payment = Payment::where('gateway_reference', $reference)->first();

        if (!$payment || $payment->status === 'success') {
            return;
        }

        $payment->update([
            'status' => 'success',
            'channel' => $data['channel'] ?? 'unknown',
            'gateway_id' => $data['id'] ?? $data['transaction_id'] ?? $data['gateway_id'] ?? null,
            'paid_at' => now(),
        ]);

        // Recalculate paid_amount from scratch by summing all successful payments.
        // Using increment() was fragile — if the stored payment amount was wrong or
        // paid_amount was stale, the invoice status would never reach 'paid'.
        $invoice = $payment->invoice;
        $totalPaid = $invoice->payments()->where('status', 'success')->sum('amount');

        $newStatus = $totalPaid >= $invoice->amount
            ? 'paid'
            : ($totalPaid > 0 ? 'partial' : 'pending');

        $invoice->update([
            'paid_amount' => $totalPaid,
            'status'      => $newStatus,
        ]);

        // Re-load the fresh invoice onto the payment model for side-effects below
        $payment->setRelation('invoice', $invoice->fresh());

        // Specific Logic based on Invoice Type
        $this->handleInvoiceTypeSideEffects($payment);

        // Send Receipt Email
        $this->sendReceipt($payment);
    }

    protected function handleInvoiceTypeSideEffects($payment)
    {
        $invoice = $payment->invoice;

        if ($invoice->type === 'acceptance_fee') {
            $applicant = Applicant::where('user_id', $payment->user_id)->first();
            if ($applicant) {
                app(EnrollmentService::class)->enroll($applicant, $payment->user_id);
            }
        }

        if ($invoice->type === 'hostel_fee') {
            $booking = \App\Models\HostelBooking::where('invoice_id', $invoice->id)->first();
            if ($booking) {
                $booking->update(['status' => 'confirmed']);
            }
        }
        
        if ($invoice->type === 'application_fee') {
            $applicant = Applicant::where('user_id', $payment->user_id)->first();
            if ($applicant && $applicant->status === 'pending_payment') {
                $applicant->update([
                    'status' => 'submitted',
                    'application_number' => \App\Helpers\ApplicationNumberHelper::generate(),
                ]);
                
                $payment->user->notify(new \App\Notifications\ApplicationSubmitted($applicant));
            }
        }
    }

    protected function sendReceipt($payment)
    {
        try {
            Mail::to($payment->user->email)->send(new FeeReceipt($payment, $payment->invoice, $payment->user));
        } catch (\Exception $e) {
            Log::error('Failed to send receipt email: ' . $e->getMessage());
        }
    }
}
