<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentSession;
use App\Services\Finance\FeeService;
use Illuminate\Console\Command;

class SyncStudentActiveSession extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:sync-active-session 
                            {--generate-invoices : Generate school fee invoices for updated students}
                            {--dry-run : Preview which students and invoices would be modified without executing database changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync active student_sessions and re-associate initiated/paid invoices to match the current active academic session.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $activeSession = Session::where('is_current', true)->first();

        if (!$activeSession) {
            $this->error('No current active session found in academic_sessions table!');
            return Command::FAILURE;
        }

        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn("=== DRY RUN MODE: No database changes will be saved ===");
        }

        $activeSemester = $activeSession->semesters()->where('is_current', true)->first();
        $semesterName = $activeSemester?->name ?? 'First Semester';

        $this->info("Current Active Session: {$activeSession->name} ({$activeSession->id})");
        $this->info("Current Active Semester: {$semesterName}");

        $feeService = app(FeeService::class);
        $shouldGenerateInvoices = $this->option('generate-invoices');

        $updatedSessionsCount = 0;
        $reassignedInvoicesCount = 0;

        Student::where('status', '!=', 'graduated')->chunkById(200, function ($students) use ($activeSession, $semesterName, $feeService, $shouldGenerateInvoices, $isDryRun, &$updatedSessionsCount, &$reassignedInvoicesCount) {
            foreach ($students as $student) {
                // Strict Guard: Check if student already has active record in the current active session
                $hasCurrentSessionRecord = StudentSession::where('student_id', $student->id)
                    ->where('session_id', $activeSession->id)
                    ->where('status', 'active')
                    ->exists();

                // 1. Ensure student has active StudentSession for current session
                if (!$hasCurrentSessionRecord) {
                    $this->line("<comment>[Session Mismatch]</comment> Student: {$student->matriculation_number} (ID: {$student->id}) lacks active session {$activeSession->name}");

                    if (!$isDryRun) {
                        // Mark old active sessions as completed
                        StudentSession::where('student_id', $student->id)
                            ->where('status', 'active')
                            ->update(['status' => 'completed']);

                        // Create or activate the record for the current active session
                        $activeStudentSession = StudentSession::updateOrCreate(
                            [
                                'student_id' => $student->id,
                                'session_id' => $activeSession->id,
                            ],
                            [
                                'level'    => $student->current_level,
                                'status'   => 'active',
                                'semester' => $semesterName,
                            ]
                        );
                    } else {
                        $activeStudentSession = null;
                    }

                    $updatedSessionsCount++;
                } else {
                    $activeStudentSession = StudentSession::where('student_id', $student->id)
                        ->where('session_id', $activeSession->id)
                        ->first();
                }

                // Check if student already has a school fee invoice for the active session
                $existingActiveInvoice = Invoice::where('user_id', $student->user_id)
                    ->where('type', 'school_fee')
                    ->where('session_id', $activeSession->id)
                    ->first();

                // 2. Strict Guard: Query ONLY invoices that belong to WRONG sessions (session_id != activeSession->id)
                $wrongInvoices = Invoice::where('user_id', $student->user_id)
                    ->where('type', 'school_fee')
                    ->where('session_id', '!=', $activeSession->id)
                    ->get();

                foreach ($wrongInvoices as $invoice) {
                    if ($invoice->paid_amount > 0 || in_array($invoice->status, ['paid', 'partial'])) {
                        $this->line("<comment>[Paid Invoice Transfer]</comment> Invoice #{$invoice->reference} (Paid: ₦{$invoice->paid_amount}) re-assigned to session {$activeSession->name}");
                        
                        if (!$isDryRun) {
                            $invoice->update([
                                'session_id'         => $activeSession->id,
                                'student_session_id' => $activeStudentSession?->id ?? $invoice->student_session_id,
                            ]);
                        }
                        $reassignedInvoicesCount++;
                    } else {
                        // Unpaid / Initiated Pending Invoice
                        if ($existingActiveInvoice && $existingActiveInvoice->id !== $invoice->id) {
                            $this->line("<comment>[Duplicate Invoice Cancelled]</comment> Pending Invoice #{$invoice->reference} cancelled because active session invoice exists.");
                            if (!$isDryRun) {
                                $invoice->update(['status' => 'cancelled']);
                            }
                        } else {
                            $this->line("<comment>[Initiated Invoice Converted]</comment> Pending Invoice #{$invoice->reference} converted to active session {$activeSession->name}");
                            if (!$isDryRun) {
                                $invoice->update([
                                    'session_id'         => $activeSession->id,
                                    'student_session_id' => $activeStudentSession?->id ?? $invoice->student_session_id,
                                ]);
                            }
                            $existingActiveInvoice = $invoice;
                            $reassignedInvoicesCount++;
                        }
                    }
                }

                // 3. Optionally generate invoice if requested
                if ($shouldGenerateInvoices && !$isDryRun) {
                    try {
                        $feeService->generateSchoolFeeInvoice($student, $activeSession);
                    } catch (\Throwable $e) {
                        $this->warn("Could not generate fee invoice for student ID {$student->id}: {$e->getMessage()}");
                    }
                }
            }
        });

        if ($isDryRun) {
            $this->warn("=== DRY RUN SUMMARY ===");
            $this->info("Would sync active session for {$updatedSessionsCount} student(s).");
            $this->info("Would transfer/update {$reassignedInvoicesCount} invoice(s).");
            $this->warn("No database modifications were made.");
        } else {
            $this->info("Successfully synced active sessions for {$updatedSessionsCount} student(s).");
            $this->info("Successfully transferred/updated {$reassignedInvoicesCount} initiated/paid invoice(s) to the active session.");
        }

        return Command::SUCCESS;
    }
}
