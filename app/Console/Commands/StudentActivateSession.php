<?php

namespace App\Console\Commands;

use App\Models\Session;
use App\Models\Student;
use App\Models\StudentSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\AcademicCacheService;

class StudentActivateSession extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'student:activate-session {sessionId : The ID of the academic session to activate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate an academic session, promote students to next level, and handle graduation.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $sessionId = $this->argument('sessionId');
        $session = Session::findOrFail($sessionId);

        \App\Jobs\Academic\PromoteStudentsJob::dispatch($session->id);

        AcademicCacheService::clearAll();
        $this->info('Promotion and invoicing jobs have been dispatched to the queue.');
    }
}
