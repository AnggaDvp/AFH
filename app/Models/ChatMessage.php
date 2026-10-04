<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Throwable;
use Illuminate\Support\Facades\Log;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_conversation_id',
        'sender_type',
        'sender_id',
        'body',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (ChatMessage $message) {
            foreach ($message->attachments as $attachment) {
                try {
                    $attachment->delete();
                } catch (Throwable $exception) {
                    Log::error('Chat attachment cleanup during message deletion failed.', [
                        'exception_type' => get_class($exception),
                    ]);
                    throw $exception;
                }
            }
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ChatAttachment::class, 'message_id');
    }
}
