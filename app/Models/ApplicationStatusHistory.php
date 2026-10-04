<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'credit_application_id', 'from_status', 'to_status', 'changed_by', 'note', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(CreditApplication::class, 'credit_application_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
