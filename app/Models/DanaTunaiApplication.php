<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DanaTunaiApplication extends Model
{
    public const STATUSES = [
        'draft', 'submitted', 'under_review', 'need_documents',
        'approved', 'rejected', 'cancelled',
    ];

    protected $fillable = [
        'application_number', 'customer_id', 'chat_conversation_id',
        'status', 'source', 'submitted_at',
    ];

    protected $casts = ['submitted_at' => 'datetime'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(DanaTunaiStatusHistory::class)->orderBy('created_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }
}
