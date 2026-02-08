<?php

use App\Jobs\ScheduledBackupJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

//log a message every minute
// Artisan::command('log:message', function () {
//     \Log::info('This is a log message from the log:message command.');
// })->purpose('Log a message every minute')->everyMinute();

// Scheduled backup commands
// Artisan::command('backup:schedule-daily', function () {
//     ScheduledBackupJob::dispatch('daily');
//     $this->info('Daily backup job dispatched successfully.');
// })->purpose('Run daily scheduled backup')->daily();

// Artisan::command('backup:schedule-weekly', function () {
//     ScheduledBackupJob::dispatch('weekly');
//     $this->info('Weekly backup job dispatched successfully.');
// })->purpose('Run weekly scheduled backup')->weekly();

// Artisan::command('backup:schedule-monthly', function () {
//     ScheduledBackupJob::dispatch('monthly');
//     $this->info('Monthly backup job dispatched successfully.');
// })->purpose('Run monthly scheduled backup')->monthly();

// // Cleanup command for old backups
// Artisan::command('backup:cleanup-old', function () {
//     $this->info('Cleaning up old backups...');

//     // Cleanup logic handled by the ScheduledBackupJob
//     // This command can also be run manually if needed
//     $job = new ScheduledBackupJob();
//     $reflection = new \ReflectionClass($job);
//     $method = $reflection->getMethod('cleanupOldBackups');
//     $method->setAccessible(true);
//     $method->invoke($job);

//     $this->info('Old backups cleanup completed.');
// })->purpose('Cleanup old backup files')->daily();

// BotBook: Generate AI bot users daily
Artisan::command('botbook:daily-bots', function () {
    $this->info('🤖 Starting daily bot user generation...');
    
    Artisan::call('botbook:generate-users', ['count' => 1]);
    
    $this->info('✅ Daily bot generation completed!');
})->purpose('Generate 5 AI bot users daily with Bangladeshi profiles')->hourly();

// BotBook: Generate fitness/health categories weekly
Artisan::command('botbook:weekly-categories', function () {
    $this->info('🏷️  Starting weekly category generation...');
    
    Artisan::call('botbook:generate-categories', ['count' => 1]);
    
    $this->info('✅ Weekly category generation completed!');
})->purpose('Generate AI-powered fitness/health categories weekly')->everyFifteenMinutes();

// BotBook: Generate AI blog posts hourly
Artisan::command('botbook:hourly-posts', function () {
    $this->info('📝 Starting hourly blog post generation...');
    
    Artisan::call('botbook:generate-posts', ['count' => 1]);
    
    $this->info('✅ Hourly post generation completed!');
})->purpose('Generate 5 AI-powered blog posts every hour')->everyFiveMinutes();
