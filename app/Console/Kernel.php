<?php

namespace App\Console;

use App\Console\Commands\Mario\MarioInitDataCommand;
use App\Console\Commands\Mario\MarioPublishCommand;
use App\Console\Commands\Mario\MarioSubscribeCommand;
use App\Console\Commands\Scanbox\InitDataCommand;
use App\Console\Commands\Scanbox\PublishCommand;
use App\Console\Commands\Scanbox\SubscribeCommand;
use App\Console\Commands\Scanbox\TimerCommand;
use App\Services\ScanBoxService;
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
        InitDataCommand::class,
        PublishCommand::class,
        SubscribeCommand::class,
        MarioInitDataCommand::class,
        MarioPublishCommand::class,
        MarioSubscribeCommand::class,
        TimerCommand::class,
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
