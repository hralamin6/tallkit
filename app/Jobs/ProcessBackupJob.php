<?php

namespace App\Jobs;

use App\Models\Backup;
use App\Models\User;
use App\Events\UserNotificationEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 3;

    public function __construct(
        protected Backup $backup,
        protected array $options = []
    ) {}

    public function handle(): void
    {
        try {
            $this->backup->markAsStarted();

            // Generate unique filename
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$timestamp}.zip";

            $this->backup->update([
                'name' => $filename,
                'path' => "backups/{$filename}",
                'disk' => 'local'
            ]);

            // Build artisan command
            $command = 'backup:run';
            $commandOptions = [
                '--only-to-disk' => 'local',
                '--filename' => $filename
            ];

            // Add specific backup options
            if (isset($this->options['only_db']) && $this->options['only_db']) {
                $commandOptions['--only-db'] = true;
            }

            if (isset($this->options['only_files']) && $this->options['only_files']) {
                $commandOptions['--only-files'] = true;
            }

            // Execute backup command
            $exitCode = Artisan::call($command, $commandOptions);

            if ($exitCode === 0) {
                // Get file size
                $filePath = $this->backup->path;
                $fileSize = null;

                if (Storage::disk('local')->exists($filePath)) {
                    $fileSize = Storage::disk('local')->size($filePath);
                }

                $this->backup->markAsCompleted($fileSize);

                // Send success notification
                $this->sendNotification('success');
            } else {
                throw new \Exception('Backup command failed with exit code: ' . $exitCode);
            }

        } catch (\Exception $e) {
            $this->backup->markAsFailed($e->getMessage());

            // Send failure notification
            $this->sendNotification('failed', $e->getMessage());

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->backup->markAsFailed($exception->getMessage());
        $this->sendNotification('failed', $exception->getMessage());
    }

    protected function sendNotification(string $status, string $errorMessage = null): void
    {
        if (!$this->backup->created_by) return;

        $user = User::find($this->backup->created_by);
        if (!$user) return;

        $title = $status === 'success'
            ? 'Backup Completed Successfully'
            : 'Backup Failed';

        $message = $status === 'success'
            ? "Backup '{$this->backup->name}' completed successfully. Size: {$this->backup->formatted_file_size}"
            : "Backup '{$this->backup->name}' failed. Error: " . ($errorMessage ?? 'Unknown error');

        event(new UserNotificationEvent(
            $user,
            $title,
            $message,
            'backup',
            [
                'backup_id' => $this->backup->id,
                'status' => $status,
                'url' => route('app.backups')
            ]
        ));
    }
}
