<?php

namespace App\Jobs\Finance;

use App\Models\Session;
use App\Models\User;
use App\Services\Finance\FeeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncLateFeeInvoicesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public string $sessionId,
        public ?string $actorId = null,
    ) {
        $this->onQueue('bulk');
    }

    public function uniqueId(): string
    {
        return "sync-late-fees-{$this->sessionId}";
    }

    public function handle(FeeService $feeService): void
    {
        $session = Session::find($this->sessionId);
        if (! $session) {
            return;
        }

        $result = $feeService->syncLateFeeOnUnpaidInvoices($session);

        if (function_exists('activity')) {
            $logger = activity('finance')
                ->performedOn($session)
                ->withProperties([
                    'session_id' => $session->id,
                    'session_name' => $session->name,
                    'late_fee_amount' => (float) ($session->late_fee_amount ?? 0),
                    'updated_count' => $result['updated'],
                    'added_count' => $result['added'],
                ]);

            if ($this->actorId && ($actor = User::find($this->actorId))) {
                $logger->causedBy($actor);
            }

            $logger->log("Synced late payment fees for {$session->name} ({$result['updated']} updated, {$result['added']} added)");
        }
    }
}
