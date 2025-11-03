<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'disk',
        'path',
        'type',
        'status',
        'file_size',
        'includes',
        'error_message',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'includes' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'file_size' => 'integer',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) return 'Unknown';

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDurationAttribute()
    {
        if (!$this->started_at || !$this->completed_at) return null;

        return $this->started_at->diffForHumans($this->completed_at, true);
    }

    // Methods
    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    public function download()
    {
        if (!$this->exists()) {
            throw new \Exception('Backup file not found');
        }

        return Storage::disk($this->disk)->download($this->path, $this->name);
    }

    public function delete(): bool
    {
        // Delete physical file first
        if ($this->exists()) {
            Storage::disk($this->disk)->delete($this->path);
        }

        // Delete record
        return parent::delete();
    }

    public function markAsStarted()
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted($fileSize = null)
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'file_size' => $fileSize,
        ]);
    }

    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }
}
