<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('fees:apply-late-payment-fines')->daily();
// Schedule::command('attendance:mark-absent')->days([1, 2, 3, 4, 5, 6])->at('18:00');
