<?php

namespace App\Jobs;

use App\Models\Bulletin;
use App\Models\User;
use App\Mail\BulletinPublished;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendBulletinEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $bulletin;

    /**
     * Create a new job instance.
     */
    public function __construct(Bulletin $bulletin)
    {
        $this->bulletin = $bulletin;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $audience = $this->bulletin->target_audience;

        $query = User::query();

        if ($audience === 'students') {
            $query->whereHas('roles', function ($q) {
                $q->where('name', 'student');
            });
        } elseif ($audience === 'staff') {
            $query->whereHas('roles', function ($q) {
                $q->where('name', '!=', 'student');
            });
        }

        // Chunk through users and queue individual emails in background
        $query->chunk(100, function ($users) {
            foreach ($users as $user) {
                Mail::to($user->email)->queue(new BulletinPublished($this->bulletin, $user));
            }
        });
    }
}
