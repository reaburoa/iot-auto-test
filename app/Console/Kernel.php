<?php

namespace App\Console;

use App\Console\Commands\Mario\MarioExportCommand;
use App\Console\Commands\Mario\MarioInitDataCommand;
use App\Console\Commands\Mario\MarioSubscribeCommand;
use App\Console\Commands\Scanbox\ScanboxExportCommand;
use App\Console\Commands\Scanbox\ScanboxInitDataCommand;
use App\Console\Commands\Scanbox\ScanboxSubscribeCommand;
use App\Console\Commands\Scanbox\ScanboxTimerCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        ScanboxInitDataCommand::class,
        ScanboxSubscribeCommand::class,
        ScanboxTimerCommand::class,
        ScanboxExportCommand::class,
        MarioExportCommand::class,
        MarioInitDataCommand::class,
        MarioSubscribeCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
