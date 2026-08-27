<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Staff;
use App\Models\Attendance;
use App\Models\Holiday;
use Illuminate\Support\Carbon;

class MarkAbsentStaff extends Command
{
    protected $signature = 'attendance:mark-absent {date? : The target date (YYYY-MM-DD)}';
    protected $description = 'Automatically mark unlogged active staff members as absent for a date';

    public function handle()
    {
        $targetDate = $this->argument('date') ? Carbon::parse($this->argument('date'))->toDateString() : now()->toDateString();

        // Check if target date is Sunday (Saturday is a working day)
        $dayOfWeek = Carbon::parse($targetDate)->dayOfWeek;
        if ($dayOfWeek === Carbon::SUNDAY) {
            $this->info("Date {$targetDate} is Sunday. Skipping absent auto-marking.");
            return 0;
        }

        // Check if target date is a public holiday
        $holiday = Holiday::whereDate('date', $targetDate)->first();
        if ($holiday) {
            $this->info("Date {$targetDate} is a public holiday ({$holiday->name}). Skipping absent auto-marking.");
            return 0;
        }

        $activeStaff = Staff::whereHas('user', fn($q) => $q->where('is_active', true))->get();
        $count = 0;

        foreach ($activeStaff as $staff) {
            $exists = Attendance::where('staff_id', $staff->id)
                ->whereDate('date', $targetDate)
                ->exists();

            if (!$exists) {
                Attendance::create([
                    'staff_id' => $staff->id,
                    'date' => $targetDate,
                    'status' => 'absent',
                    'source' => 'system',
                    'notes' => 'Auto-marked absent (unlogged attendance)',
                ]);
                $count++;
            }
        }

        $this->info("Successfully marked {$count} active staff members as absent for {$targetDate}.");
        return 0;
    }
}
