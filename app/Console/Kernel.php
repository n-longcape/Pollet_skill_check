<?php

namespace App\Console;

use App\Console\Commands\MonthlyCalculatePoint;
use App\Console\Commands\ProvidePoint;
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
        MonthlyCalculatePoint::class,
        ProvidePoint::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return voikd
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('command:MonthlyCalculatePoint')->monthlyOn(5, '02:00');
        $schedule->command('command:ProvidePoint')->monthlyOn(10, '00:00');
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
