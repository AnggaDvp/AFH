<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MotorBrochureItem extends Model
{
    protected $fillable = [
        'brochure_id',
        'motor_product_id',
        'cash_price',
    ];

    protected $casts = [
        'cash_price' => 'integer',
    ];

    public function brochure(): BelongsTo
    {
        return $this->belongsTo(MotorBrochure::class, 'brochure_id');
    }

    public function motorProduct(): BelongsTo
    {
        return $this->belongsTo(MotorProduct::class);
    }

    public function financingOptions(): HasMany
    {
        return $this->hasMany(MotorFinancingOption::class, 'brochure_item_id');
    }
}
