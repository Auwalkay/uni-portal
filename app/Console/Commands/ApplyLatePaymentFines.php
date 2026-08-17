<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplyLatePaymentFines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:apply-late-payment-fines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply late payment fines to school fee invoices past their session deadline';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Check if late fees are enabled globally
        $isEnabled = filter_var(SystemSetting::get('late_fee_enabled', true), FILTER_VALIDATE_BOOLEAN);
        if (!$isEnabled) {
            $this->info('Late payment fines are globally disabled.');
            return 0;
        }

        $now = now();
        $appliedCount = 0;

        // 2. Find pending school fee invoices where the session has a deadline that has passed
        // and which do not have a late fine applied yet
        $invoices = Invoice::where('type', 'school_fee')
            ->where('status', 'pending')
            ->where('late_fine_applied', false)
            ->whereHas('session', function ($query) use ($now) {
                $query->whereNotNull('late_payment_deadline')
                      ->where('late_payment_deadline', '<', $now)
                      ->where('late_fee_amount', '>', 0);
            })
            ->with('session')
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No overdue invoices found that need late payment fines.');
            return 0;
        }

        $this->info("Found {$invoices->count()} overdue invoice(s). Appending late payment fines...");

        foreach ($invoices as $invoice) {
            $fineAmount = (float) $invoice->session->late_fee_amount;
            DB::transaction(function () use ($invoice, $fineAmount, &$appliedCount) {
                // Add late payment fine line item
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => 'Late Payment Fine (' . $invoice->session->name . ')',
                    'amount' => $fineAmount,
                ]);

                // Increment invoice total amount
                $invoice->increment('amount', $fineAmount);
                $invoice->update([
                    'late_fine_applied' => true
                ]);

                $appliedCount++;
                Log::info("Applied late payment fine of ₦" . number_format($fineAmount) . " to invoice ID: {$invoice->id} (Ref: {$invoice->reference})");
            });
        }

        $this->info("Successfully applied late payment fines to {$appliedCount} invoice(s).");
        return 0;
    }
}
