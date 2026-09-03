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

        Log::info('[PAYMENT_SUCCESS] Payment Processed & Confirmed', [
            'payment_id' => $payment->id,
            'gateway_reference' => $reference,
            'amount' => $payment->amount,
            'invoice_id' => $payment->invoice_id,
            'user_id' => $payment->user_id,
            'raw_gateway_data' => $data,
        ]);

        // Increment paid amount safely
        if ($payment->invoice) {
            $payment->invoice->increment('paid_amount', $payment->amount);
            $payment->invoice->refresh();

            // Update invoice status
            if ($payment->invoice->paid_amount >= $payment->invoice->amount) {
                $payment->invoice->update(['status' => 'paid']);
            } else {
                $payment->invoice->update(['status' => 'partial']);
            }

            // Specific Logic based on Invoice Type
            $this->handleInvoiceTypeSideEffects($payment);
        }

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

        if ($invoice->type === 'school_fee' && $invoice->status === 'paid') {
            $student = \App\Models\Student::where('user_id', $payment->user_id)->first();
            if ($student) {
                $student->checkAndPromoteStudent();
            }
        }
    }

    protected function sendReceipt($payment)
    {
        try {
            if ($payment->user && $payment->user->email) {
                Mail::to($payment->user->email)->send(new FeeReceipt($payment, $payment->invoice, $payment->user));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send receipt email: ' . $e->getMessage());
        }
    }
}
