<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'parent_id',
        'body',
        'read_at',
        'edited_at',
        'is_deleted',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'edited_at' => 'datetime',
        'is_deleted' => 'boolean',
    ];

    protected $with = ['attachments', 'reactions'];

    /**
     * Get the conversation that owns the message.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user that sent the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent message (for threading).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    /**
     * Get replies to this message.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'parent_id');
    }

    /**
     * Get attachments for the message.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    /**
     * Get reactions for the message.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * Scope to get only non-deleted messages.
     */
    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope to get only parent messages (not replies).
     */
    public function scopeParentMessages(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Check if message is edited.
     */
    public function isEdited(): bool
    {
        return !is_null($this->edited_at);
    }

    /**
     * Soft delete message (keep in DB but mark as deleted).
     */
    public function softDeleteMessage(): void
    {
        $this->update([
            'is_deleted' => true,
            'body' => null,
        ]);

        // Delete attachments
        $this->attachments()->delete();
    }

    /**
     * Get grouped reactions with counts.
     */
    public function getGroupedReactions(): array
    {
        return $this->reactions()
            ->selectRaw('emoji, count(*) as count, GROUP_CONCAT(user_id) as user_ids')
            ->groupBy('emoji')
            ->get()
            ->map(function ($reaction) {
                return [
                    'emoji' => $reaction->emoji,
                    'count' => $reaction->count,
                    'user_ids' => explode(',', $reaction->user_ids),
                ];
            })
            ->toArray();
    }
}
