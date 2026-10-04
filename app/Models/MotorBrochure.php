<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class MotorBrochure extends Model
{
    protected $fillable = [
        'period_label',
        'source_name',
        'status',
        'notes',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MotorBrochureItem::class, 'brochure_id');
    }

    public function financingOptions(): HasManyThrough
    {
        return $this->hasManyThrough(
            MotorFinancingOption::class,
            MotorBrochureItem::class,
            'brochure_id',
            'brochure_item_id',
            'id',
            'id'
        );
    }
}
