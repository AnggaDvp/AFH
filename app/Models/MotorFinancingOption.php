<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MotorFinancingOption extends Model
{
    protected $fillable = [
        'brochure_item_id',
        'down_payment_source_value',
        'tenor_code',
        'tenor_label',
        'installment_source_value',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function brochureItem(): BelongsTo
    {
        return $this->belongsTo(MotorBrochureItem::class, 'brochure_item_id');
    }
}
