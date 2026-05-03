<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ResendStaffLoginCredentials implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $staffProfiles = \App\Models\Staff::with('user')->get();

        foreach ($staffProfiles as $staff) {
            $user = $staff->user;
            if (!$user) continue;

            $password = \Illuminate\Support\Str::random(10);
            $user->update([
                'password' => \Illuminate\Support\Facades\Hash::make($password)
            ]);

            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\StaffAccountCreated($user, $password)
            );
        }
    }
}
