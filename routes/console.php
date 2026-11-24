<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal pembersihan berkas dataset yatim piatu di storage/app secara berkala
Schedule::command('dataset:cleanup-storage --force')
    ->weeklyOn(0, '02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// Prune jobs yang gagal setelah 7 hari
Schedule::command('queue:prune-failed --hours=168')
    ->weeklyOn(0, '03:00');

