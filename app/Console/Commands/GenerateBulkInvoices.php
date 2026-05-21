<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Session;
use App\Services\Finance\FeeService;

class GenerateBulkInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:generate-bulk {session_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bulk generate school fee invoices for students for a specific session';

    /**
     * Execute the console command.
     */
    public function handle(FeeService $feeService)
    {
        $sessionId = $this->argument('session_id');
        
        if ($sessionId) {
            $session = Session::find($sessionId);
        } else {
            $session = Session::current();
        }

        if (!$session) {
            $this->error('No active academic session found.');
            return 1;
        }

        $this->info("Generating invoices for session: {$session->name}");

        $count = $feeService->bulkGenerateInvoices($session);

        $this->info("Successfully generated/refreshed {$count} invoices.");
        
        return 0;
    }
}
