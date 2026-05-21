<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\Payment\PaymentHandler;
use App\Services\PaystackService;
use App\Services\SquadcoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RequeryPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:requery {--limit=50 : Number of payments to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Requery pending payments from gateways and update status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        
        $pendingPayments = Payment::where('status', 'pending')
            ->where('gateway_reference', 'NOT LIKE', 'TEMP-%') // Ignore temporary local refs
            ->where('created_at', '<', now()->subMinutes(5)) // Give it a few minutes to breathe
            ->latest()
            ->limit($limit)
            ->get();

        $this->info("Found {$pendingPayments->count()} pending payments to requery.");

        if ($pendingPayments->isEmpty()) {
            return Command::SUCCESS;
        }

        $handler = app(PaymentHandler::class);
        $squadco = new SquadcoService();
        $paystack = new PaystackService();

        $successCount = 0;
        $failedCount = 0;

        foreach ($pendingPayments as $payment) {
            try {
                $this->comment("Checking reference: {$payment->gateway_reference} (Gateway: {$payment->gateway})");
                
                $gateway = ($payment->gateway === 'paystack') ? $paystack : $squadco;
                $data = $gateway->verifyTransaction($payment->gateway_reference);

                if ($data && $data['status'] === 'success') {
                    $handler->handleSuccessfulPayment($payment->gateway_reference, $data);
                    $this->info("✓ Payment {$payment->gateway_reference} verified as SUCCESS.");
                    $successCount++;
                } elseif ($data && in_array($data['status'], ['failed', 'cancelled', 'error'])) {
                    $payment->update(['status' => 'failed']);
                    $this->warn("✗ Payment {$payment->gateway_reference} marked as FAILED.");
                    $failedCount++;
                } else {
                    $this->line("- Payment {$payment->gateway_reference} is still pending on gateway.");
                }

            } catch (\Exception $e) {
                $this->error("Error requerying {$payment->gateway_reference}: " . $e->getMessage());
                Log::error("Payment Requery Error: " . $e->getMessage(), [
                    'payment_id' => $payment->id,
                    'reference' => $payment->gateway_reference
                ]);
            }
        }

        $this->info("Requery process completed. Successes: {$successCount}, Failures: {$failedCount}");
        
        return Command::SUCCESS;
    }
}
