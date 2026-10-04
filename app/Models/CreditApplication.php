<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditApplication extends Model
{
    public const STATUSES = [
        'draft', 'submitted', 'under_review', 'need_documents',
        'approved', 'rejected', 'cancelled',
    ];

    protected $fillable = [
        'application_number', 'customer_id', 'motor_product_id', 'brochure_item_id',
        'cash_price', 'down_payment', 'tenor_months', 'installment_amount',
        'installment_source', 'status', 'source', 'submitted_at',
    ];

    protected $casts = [
        'cash_price' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function motorProduct(): BelongsTo
    {
        return $this->belongsTo(MotorProduct::class);
    }

    public function brochureItem(): BelongsTo
    {
        return $this->belongsTo(MotorBrochureItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->orderBy('created_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }
}
