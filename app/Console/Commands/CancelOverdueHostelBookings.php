<?php

namespace App\Console\Commands;

use App\Models\HostelBooking;
use App\Models\Payment;
use App\Services\Payment\PaymentHandler;
use App\Services\PaystackService;
use App\Services\SquadcoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelOverdueHostelBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hostels:cancel-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel pending hostel bookings that are past their payment due date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Checking for overdue hostel bookings...");

        // Find bookings that are pending where the associated invoice's due_date is in the past
        // and the invoice is unpaid.
        $overdueBookings = HostelBooking::where('status', 'pending')
            ->whereHas('invoice', function ($query) {
                $query->where('status', 'pending')
                    ->where('due_date', '<', now());
            })
            ->with(['invoice.payments' => function ($q) {
                $q->where('status', 'pending');
            }])
            ->get();

        $this->info("Found {$overdueBookings->count()} potentially overdue hostel bookings.");

        if ($overdueBookings->isEmpty()) {
            return Command::SUCCESS;
        }

        $handler = app(PaymentHandler::class);
        $squadco = new SquadcoService();
        $paystack = new PaystackService();
        $cancelledCount = 0;

        foreach ($overdueBookings as $booking) {
            $invoice = $booking->invoice;
            if (!$invoice) {
                continue;
            }

            // Before removing, try to verify any pending payments for this invoice
            $pendingPayments = $invoice->payments;
            $paymentVerifiedSuccessful = false;

            foreach ($pendingPayments as $payment) {
                if ($payment->gateway_reference && strpos($payment->gateway_reference, 'TEMP-') !== 0) {
                    try {
                        $this->comment("Verifying pending payment: {$payment->gateway_reference} for student booking...");
                        $gateway = ($payment->gateway === 'paystack') ? $paystack : $squadco;
                        $data = $gateway->verifyTransaction($payment->gateway_reference);

                        if ($data && $data['status'] === 'success') {
                            $handler->handleSuccessfulPayment($payment->gateway_reference, $data);
                            $this->info("✓ Booking payment {$payment->gateway_reference} verified as SUCCESS. Booking confirmed.");
                            $paymentVerifiedSuccessful = true;
                            break; // Stop verifying other payments for this invoice
                        } elseif ($data && in_array($data['status'], ['failed', 'cancelled', 'error'])) {
                            $payment->update(['status' => 'failed']);
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to verify booking payment {$payment->gateway_reference}: " . $e->getMessage());
                    }
                }
            }

            // Refresh the booking and invoice to check if they got confirmed/paid
            $booking->refresh();

            if ($booking->status === 'pending') {
                $this->info("Cancelling overdue pending booking for student ID: {$booking->student_id}");
                DB::transaction(function () use ($booking) {
                    if ($booking->invoice) {
                        $booking->invoice->items()->delete();
                        $booking->invoice->delete();
                    }
                    $booking->delete();
                });
                $cancelledCount++;
            }
        }

        $this->info("Successfully cancelled and nullified {$cancelledCount} overdue hostel bookings.");
        return Command::SUCCESS;
    }
}
