<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\SyncTrmRatesJob;
use App\Jobs\RecordatorioAprobacionEtapaJob;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new SyncTrmRatesJob())->dailyAt('00:15');
        $schedule->job(new RecordatorioAprobacionEtapaJob())->dailyAt('08:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}