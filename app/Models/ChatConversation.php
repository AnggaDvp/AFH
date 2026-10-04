<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatConversation extends Model
{
    protected $fillable = [
        'customer_id',
        'context_type',
        'motor_product_id',
        'status',
        'last_message_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'motor_product_id' => 'integer',
        'unread_count' => 'integer',
        'last_message_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (ChatConversation $conversation) {
            foreach ($conversation->messages()->with('attachments')->get() as $message) {
                foreach ($message->attachments as $attachment) {
                    try {
                        $attachment->delete();
                    } catch (Throwable $exception) {
                        Log::error('Chat attachment cleanup during conversation deletion failed.', [
                            'exception_type' => get_class($exception),
                        ]);
                        throw $exception;
                    }
                }
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function motorProduct(): BelongsTo
    {
        return $this->belongsTo(MotorProduct::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public function danaTunaiApplication(): HasOne
    {
        return $this->hasOne(DanaTunaiApplication::class);
    }

    public function latestCashLoanRequest(): HasOne
    {
        return $this->hasOne(CashLoanRequest::class, 'conversation_id')->latestOfMany();
    }
}
