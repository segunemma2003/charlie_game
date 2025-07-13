<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\ProcessExpiredListings::class,
        Commands\BackupGameData::class,
        Commands\ExportAnalytics::class,
        Commands\UpdateCryptoPrices::class,
        Commands\ProcessPendingPayments::class,
        Commands\CleanupOldBattles::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Process expired marketplace listings every 5 minutes
        $schedule->command('marketplace:process-expired')
                 ->everyFiveMinutes()
                 ->withoutOverlapping();

        // Check for pending crypto payments every minute
        $schedule->command('crypto:check-payments')
                 ->everyMinute()
                 ->withoutOverlapping();

        // Update crypto prices every 10 minutes
        $schedule->command('crypto:update-prices')
                 ->everyTenMinutes();

        // Backup game data daily at 2 AM
        $schedule->command('game:backup --type=full')
                 ->dailyAt('02:00')
                 ->emailOutputOnFailure('admin@charlieunicorn.com');

        // Export analytics weekly
        $schedule->command('game:export-analytics --period=7')
                 ->weekly()
                 ->mondays()
                 ->at('03:00');

        // Clean up old completed battles (older than 30 days)
        $schedule->command('battles:cleanup')
                 ->daily()
                 ->at('04:00');

        // Clear expired tokens and sessions
        $schedule->command('sanctum:prune-expired --hours=168') // 1 week
                 ->daily();

        // Send tournament reminders
        $schedule->command('tournaments:send-reminders')
                 ->hourly();

        // Update user skill levels based on recent performance
        $schedule->command('users:update-skill-levels')
                 ->daily()
                 ->at('05:00');

        // Clear cache for leaderboards
        $schedule->call(function () {
            \Illuminate\Support\Facades\Cache::tags(['leaderboards'])->flush();
        })->everyThirtyMinutes();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
