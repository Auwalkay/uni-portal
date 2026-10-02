<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RemoveFreshStudentLateFines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:remove-fresh-student-late-fines {--dry-run : Run without modifying database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove erroneous late payment fines applied to fresh (newly admitted) students';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->info('[DRY RUN MODE] No database changes will be committed.');
        }

        // Find school fee invoices with late fines applied or fine line items
        $invoices = Invoice::where('type', 'school_fee')
            ->where(function ($q) {
                $q->where('late_fine_applied', true)
                  ->orWhereHas('items', function ($iq) {
                      $iq->where('description', 'LIKE', '%Late Payment Fine%')
                         ->orWhere('description', 'LIKE', '%Late Fee%');
                  });
            })
            ->with(['items', 'session'])
            ->get();

        $affectedCount = 0;
        $totalFinesRemoved = 0;

        foreach ($invoices as $invoice) {
            $student = Student::where('user_id', $invoice->user_id)->first();
            if (!$student) {
                continue;
            }

            // Check if the student is a fresh student for this invoice's session (NOT a returning student)
            if ($student->isReturningStudent($invoice->session_id)) {
                continue; // Skip returning students; late fines are valid for them
            }

            // Find fine line items
            $fineItems = $invoice->items->filter(function ($item) {
                $desc = strtolower($item->description);
                return str_contains($desc, 'late payment fine') || str_contains($desc, 'late fee');
            });

            $fineAmountSum = $fineItems->sum('amount');
            if ($fineAmountSum <= 0 && !$invoice->late_fine_applied) {
                continue;
            }

            $affectedCount++;
            $totalFinesRemoved += $fineAmountSum;

            $this->info("Found fresh student invoice ID {$invoice->id} (User: {$invoice->user_id}, Ref: {$invoice->reference}): Fine Amount = ₦" . number_format($fineAmountSum, 2));

            if (!$isDryRun) {
                DB::transaction(function () use ($invoice, $fineItems, $fineAmountSum) {
                    foreach ($fineItems as $item) {
                        $item->delete();
                    }

                    $newAmount = max(0, (float) $invoice->amount - $fineAmountSum);
                    $newStatus = ($invoice->paid_amount >= $newAmount && $newAmount > 0) ? 'paid' : ($invoice->paid_amount > 0 ? 'partial' : 'pending');

                    $invoice->update([
                        'amount' => $newAmount,
                        'status' => $newStatus,
                        'late_fine_applied' => false,
                    ]);

                    Log::info("[CLEANUP_LATE_FINE] Removed late fine of ₦{$fineAmountSum} from fresh student invoice ID {$invoice->id}");
                });
            }
        }

        if ($isDryRun) {
            $this->info("Dry run complete. Would fix {$affectedCount} invoice(s) removing a total of ₦" . number_format($totalFinesRemoved, 2) . " in late fines.");
        } else {
            $this->info("Cleanup complete. Successfully fixed {$affectedCount} invoice(s) and removed ₦" . number_format($totalFinesRemoved, 2) . " in late fines.");
        }

        return 0;
    }
}
