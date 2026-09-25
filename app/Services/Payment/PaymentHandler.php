<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Applicant;
use App\Services\EnrollmentService;
use App\Mail\FeeReceipt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PaymentHandler
{
    /**
     * Initiate a payment transaction with the appropriate gateway for an invoice.
     *
     * @param Invoice $invoice
     * @param \App\Models\User $user
     * @param float $amount
     * @param string $callbackUrl
     * @param array $metadata
     * @return array|null
     */
    public function initiatePayment(Invoice $invoice, \App\Models\User $user, float $amount, string $callbackUrl, array $metadata = []): ?array
    {
        [$activeGatewayName, $gateway] = PaymentGatewayFactory::resolveWithGatewayName($invoice);

        $reference = Payment::generateReference('PAY');
        $transactionId = Payment::generateTransactionId('MIUPAY');

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'transaction_id' => $transactionId,
            'gateway' => $activeGatewayName,
            'gateway_reference' => $reference,
            'amount' => $amount,
            'status' => 'pending',
        ]);

        $defaultMetadata = [
            'customer_name' => $user->name,
            'payment_type' => $invoice->type,
            'invoice_id' => $invoice->id,
        ];

        $mergedMetadata = array_merge($defaultMetadata, $metadata);

        $initData = $gateway->initializeTransaction(
            $user->email,
            $amount,
            $reference,
            $callbackUrl,
            $mergedMetadata
        );

        if ($initData && !empty($initData['authorization_url'])) {
            return [
                'payment' => $payment,
                'authorization_url' => $initData['authorization_url'],
                'gateway' => $activeGatewayName,
                'reference' => $reference,
                'raw' => $initData,
            ];
        }

        $payment->update(['status' => 'failed']);

        Log::error('[PAYMENT_INITIATION_FAILED] Gateway initialization returned null or missing authorization_url', [
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => $amount,
            'gateway' => $activeGatewayName,
            'reference' => $reference,
        ]);

        return null;
    }

    /**
     * Verify a transaction with its respective gateway and process payment if successful.
     *
     * @param string $reference
     * @param Invoice|null $invoice
     * @param string|null $fallbackGateway
     * @return array ['status' => 'success'|'failed'|'pending', 'payment' => Payment|null, 'data' => array|null]
     */
    public function verifyAndProcessPayment(string $reference, ?Invoice $invoice = null, ?string $fallbackGateway = null): array
    {
        $payment = Payment::where('gateway_reference', $reference)->first();
        $targetInvoice = $invoice ?? $payment?->invoice;
        $targetGatewayName = $fallbackGateway ?? $payment?->gateway;

        $gateway = PaymentGatewayFactory::resolveForInvoice($targetInvoice, $targetGatewayName);
        $data = $gateway->verifyTransaction($reference);

        if (!$data) {
            return [
                'status' => 'pending',
                'payment' => $payment,
                'data' => null,
            ];
        }

        $rawStatus = (string) ($data['status'] ?? '');

        if (Payment::isSuccessStatus($rawStatus)) {
            if ($payment && $payment->status !== 'success') {
                $this->handleSuccessfulPayment($reference, $data);
            }

            return [
                'status' => 'success',
                'payment' => $payment?->fresh(),
                'data' => $data,
            ];
        }

        if (Payment::isFailedStatus($rawStatus)) {
            if ($payment && $payment->status !== 'success') {
                $payment->update(['status' => 'failed']);
            }

            return [
                'status' => 'failed',
                'payment' => $payment?->fresh(),
                'data' => $data,
            ];
        }

        return [
            'status' => 'pending',
            'payment' => $payment,
            'data' => $data,
        ];
    }

    public function handleSuccessfulPayment($reference, $data)
    {
        $payment = Payment::where('gateway_reference', $reference)->first();

        if (!$payment || $payment->status === 'success') {
            return;
        }

        $rawPaidAt = $data['paid_at'] 
            ?? $data['created_at'] 
            ?? $data['paidAt'] 
            ?? $data['transaction_date'] 
            ?? $data['original_data']['created_at'] 
            ?? $data['original_data']['transaction_date'] 
            ?? null;

        $paidAt = null;
        if ($rawPaidAt) {
            try {
                $paidAt = \Carbon\Carbon::parse($rawPaidAt);
            } catch (\Throwable $e) {
                $paidAt = now();
            }
        } else {
            $paidAt = now();
        }

        $payment->update([
            'status' => 'success',
            'channel' => $data['channel'] ?? $data['payment_method'] ?? $payment->channel ?? 'unknown',
            'gateway_id' => $data['id'] ?? $data['transaction_id'] ?? $data['gateway_id'] ?? null,
            'paid_at' => $paidAt,
        ]);

        Log::info('[PAYMENT_SUCCESS] Payment Processed & Confirmed', [
            'payment_id' => $payment->id,
            'gateway_reference' => $reference,
            'amount' => $payment->amount,
            'invoice_id' => $payment->invoice_id,
            'user_id' => $payment->user_id,
            'raw_gateway_data' => $data,
        ]);

        // Increment paid amount safely, ensuring total paid never exceeds invoice total amount
        if ($payment->invoice) {
            $invoice = $payment->invoice;

            // Remove late payment fine if payment was made on or before the late payment deadline
            $this->checkAndRemoveLateFineIfPaidBeforeDeadline($invoice, $paidAt);

            $currentPaid = (float) $invoice->paid_amount;
            $invoiceAmount = (float) $invoice->amount;
            $newPaid = min($invoiceAmount, $currentPaid + (float) $payment->amount);

            $invoice->update([
                'paid_amount' => $newPaid,
                'status' => ($newPaid >= $invoiceAmount && $invoiceAmount > 0) ? 'paid' : ($newPaid > 0 ? 'partial' : 'pending'),
            ]);
            $invoice->refresh();

            // Specific Logic based on Invoice Type
            $this->handleInvoiceTypeSideEffects($payment);
        }

        // Send Receipt Email
        $this->sendReceipt($payment);
    }

    public function checkAndRemoveLateFineIfPaidBeforeDeadline($invoice, $paidAt): void
    {
        if (!$invoice || !$paidAt) {
            return;
        }

        // Determine effective late payment deadline (session deadline or invoice due date)
        $deadline = $invoice->session?->late_payment_deadline ?? $invoice->due_date;
        if (!$deadline) {
            return;
        }

        $paymentDate = \Carbon\Carbon::parse($paidAt);
        $deadlineDate = \Carbon\Carbon::parse($deadline);

        // If payment was made on or before the late payment deadline
        if ($paymentDate->lte($deadlineDate)) {
            $lateFineItems = \App\Models\InvoiceItem::where('invoice_id', $invoice->id)
                ->where(function ($query) {
                    $query->where('description', 'LIKE', '%Late Payment Fine%')
                          ->orWhere('description', 'LIKE', '%Late Fee%');
                })
                ->get();

            if ($lateFineItems->isNotEmpty() || $invoice->late_fine_applied) {
                $totalFineAmount = 0;
                foreach ($lateFineItems as $item) {
                    $totalFineAmount += (float) $item->amount;
                    $item->delete();
                }

                $newAmount = max(0, (float) $invoice->amount - $totalFineAmount);

                $invoice->update([
                    'amount' => $newAmount,
                    'late_fine_applied' => false,
                ]);
                $invoice->refresh();

                Log::info('[LATE_FINE_REMOVED] Removed late payment fine because payment date is on or before deadline.', [
                    'invoice_id' => $invoice->id,
                    'payment_date' => $paymentDate->toIso8601String(),
                    'deadline' => $deadlineDate->toIso8601String(),
                    'fine_removed' => $totalFineAmount,
                    'new_invoice_amount' => $newAmount,
                ]);
            }
        }
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
                $room = $booking->room;
                if ($room) {
                    $otherConfirmedCount = \App\Models\HostelBooking::where('hostel_room_id', $room->id)
                        ->where('session_id', $booking->session_id)
                        ->where('status', 'confirmed')
                        ->where('id', '!=', $booking->id)
                        ->count();

                    if ($otherConfirmedCount < $room->capacity) {
                        $booking->update(['status' => 'confirmed']);
                    } else {
                        Log::warning('[HOSTEL_OVERBOOKING_PREVENTED] Hostel booking payment confirmed for cancelled/expired reservation, but room capacity has been filled.', [
                            'booking_id' => $booking->id,
                            'invoice_id' => $invoice->id,
                            'user_id' => $payment->user_id,
                            'room_id' => $room->id,
                        ]);
                    }
                } else {
                    $booking->update(['status' => 'confirmed']);
                }
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
