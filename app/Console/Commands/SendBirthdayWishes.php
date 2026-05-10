<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Notifications\BirthdayWish;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendBirthdayWishes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:birthday-wishes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send birthday wishes to students celebrating today';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->format('m-d');
        
        $students = Student::with('user')
            ->whereNotNull('dob')
            ->whereRaw("DATE_FORMAT(dob, '%m-%d') = ?", [$today])
            ->get();

        if ($students->isEmpty()) {
            $this->info("No students celebrating their birthday today ({$today}).");
            return Command::SUCCESS;
        }

        $this->info("Sending birthday wishes to {$students->count()} students...");

        foreach ($students as $student) {
            try {
                if ($student->user) {
                    $student->user->notify(new BirthdayWish($student->user->name));
                    $this->line("Sent birthday wish to: {$student->user->name} ({$student->user->email})");
                }
            } catch (\Exception $e) {
                $this->error("Failed to send wish to {$student->user->name}: " . $e->getMessage());
                Log::error("Birthday Wish Error: " . $e->getMessage(), [
                    'student_id' => $student->id,
                    'user_id' => $student->user_id ?? null
                ]);
            }
        }

        $this->info("All birthday wishes processed.");
        
        return Command::SUCCESS;
    }
}
