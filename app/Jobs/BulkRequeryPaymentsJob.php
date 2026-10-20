<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\PaymentHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BulkRequeryPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public array $filters;
    public ?User $adminUser;

    /**
     * Create a new job instance.
     */
    public function __construct(array $filters = [], ?User $adminUser = null)
    {
        $this->filters = $filters;
        $this->adminUser = $adminUser;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[BULK_REQUERY_JOB_STARTED] Starting bulk payment requery job.', [
            'filters' => $this->filters,
            'admin_id' => $this->adminUser?->id,
        ]);

        $query = Payment::query()
            ->whereNotNull('gateway_reference')
            ->where('gateway_reference', '!=', '')
            ->whereIn('status', ['failed', 'pending']);

        // Filter by session if provided
        if (!empty($this->filters['session_id']) && $this->filters['session_id'] !== 'ALL_SESSIONS_RESET_VALUE') {
            $sessionId = $this->filters['session_id'];
            $query->whereHas('invoice', function ($q) use ($sessionId) {
                $q->where('session_id', $sessionId);
            });
        }

        // Filter by department if provided
        if (!empty($this->filters['department_id']) && $this->filters['department_id'] !== 'ALL_DEPARTMENTS_RESET_VALUE') {
            $departmentId = $this->filters['department_id'];
            $query->whereHas('user.student', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        // Filter by faculty if provided
        if (!empty($this->filters['faculty_id']) && $this->filters['faculty_id'] !== 'ALL_FACULTIES_RESET_VALUE' && empty($this->filters['department_id'])) {
            $facultyId = $this->filters['faculty_id'];
            $query->whereHas('user.student', function ($q) use ($facultyId) {
                $q->where('faculty_id', $facultyId);
            });
        }

        // Search filter if provided
        if (!empty($this->filters['search'])) {
            $search = trim($this->filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('gateway_reference', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Date Range Filters
        if (!empty($this->filters['start_date'])) {
            $query->whereRaw('COALESCE(paid_at, created_at) >= ?', [$this->filters['start_date'] . ' 00:00:00']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereRaw('COALESCE(paid_at, created_at) <= ?', [$this->filters['end_date'] . ' 23:59:59']);
        }

        $paymentHandler = app(PaymentHandler::class);
        $successfulCount = 0;
        $failedCount = 0;
        $errorCount = 0;
        $totalProcessed = 0;

        $query->chunk(50, function ($payments) use ($paymentHandler, &$successfulCount, &$failedCount, &$errorCount, &$totalProcessed) {
            foreach ($payments as $payment) {
                $totalProcessed++;
                try {
                    $gatewayName = strtolower($payment->gateway ?? 'squadco');
                    $gatewayService = PaymentGatewayFactory::resolveForInvoice($payment->invoice, $gatewayName);
                    $paymentData = $gatewayService->verifyTransaction($payment->gateway_reference);

                    $status = strtolower($paymentData['status'] ?? $paymentData['transaction_status'] ?? '');
                    $isSuccess = in_array($status, ['success', 'successful', 'approved', 'completed', 'paid']);

                    if ($paymentData && $isSuccess) {
                        if ($payment->status !== 'success') {
                            $paymentHandler->handleSuccessfulPayment($payment->gateway_reference, $paymentData);
                            $successfulCount++;
                        }
                    } else {
                        if ($payment->status !== 'success') {
                            $payment->update(['status' => 'failed']);
                            $failedCount++;
                        }
                    }
                } catch (\Throwable $e) {
                    $errorCount++;
                    Log::error("[BULK_REQUERY_JOB_ERROR] Payment ID: {$payment->id}", [
                        'gateway_reference' => $payment->gateway_reference,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        Log::info('[BULK_REQUERY_JOB_COMPLETED] Bulk requery job finished.', [
            'total_processed' => $totalProcessed,
            'successful' => $successfulCount,
            'failed' => $failedCount,
            'errors' => $errorCount,
        ]);
    }
}
