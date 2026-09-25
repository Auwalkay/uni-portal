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
    protected $signature = 'payments:requery {--limit=100 : Number of payments to check}';

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
        $limit = (int) $this->option('limit');
        
        // Only query PENDING payments from the last 7 days that are at least 5 minutes old
        $payments = Payment::where('status', 'pending')
            ->whereNotNull('gateway_reference')
            ->where('gateway_reference', '!=', '')
            ->where('gateway_reference', 'NOT LIKE', 'TEMP-%')
            ->where('created_at', '>=', now()->subDays(7))
            ->where('created_at', '<=', now()->subMinutes(5)) // Allow 5 minutes for active user checkout
            ->latest('created_at')
            ->limit($limit)
            ->get();

        $this->info("Found {$payments->count()} pending payments to requery.");

        if ($payments->isEmpty()) {
            return Command::SUCCESS;
        }

        $handler = app(PaymentHandler::class);

        $successCount = 0;
        $failedCount = 0;

        foreach ($payments as $payment) {
            try {
                $this->comment("Checking reference: {$payment->gateway_reference} (Gateway: {$payment->gateway})");

                $result = $handler->verifyAndProcessPayment($payment->gateway_reference, $payment->invoice, $payment->gateway);

                if ($result['status'] === 'success') {
                    $this->info("✓ Payment {$payment->gateway_reference} verified as SUCCESS.");
                    $successCount++;
                } elseif ($result['status'] === 'failed' || $payment->created_at->lt(now()->subHours(24))) {
                    if ($payment->status !== 'failed') {
                        $payment->update(['status' => 'failed']);
                    }
                    $status = $result['data']['status'] ?? 'failed';
                    $this->warn("✗ Pending payment {$payment->gateway_reference} (Gateway status: {$status}) marked as FAILED.");
                    $failedCount++;
                } else {
                    $this->line("- Payment {$payment->gateway_reference} remains pending on gateway.");
                }

            } catch (\Exception $e) {
                $this->error("Error requerying {$payment->gateway_reference}: " . $e->getMessage());
                Log::error("Payment Requery Error: " . $e->getMessage(), [
                    'payment_id' => $payment->id,
                    'reference' => $payment->gateway_reference
                ]);
            }
        }

        $this->info("Requery process completed. Re-verified Successes: {$successCount}, Marked Failed: {$failedCount}");
        
        return Command::SUCCESS;
    }
}
