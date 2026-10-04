<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDocument extends Model
{
    protected $fillable = [
        'customer_id', 'credit_application_id', 'dana_tunai_application_id',
        'document_type', 'original_name', 'disk', 'path', 'mime_type',
        'size', 'status', 'uploaded_at', 'reviewed_by', 'reviewed_at',
    ];

    protected $hidden = ['path', 'disk'];

    protected $casts = [
        'size' => 'integer',
        'uploaded_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creditApplication(): BelongsTo
    {
        return $this->belongsTo(CreditApplication::class);
    }

    public function danaTunaiApplication(): BelongsTo
    {
        return $this->belongsTo(DanaTunaiApplication::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
