<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'full_name',
        'phone',
        'region_id',
        'district_id',
        'village_id',
        'postal_code',
        'street_address',
    ];

    protected $casts = [
        'region_id' => 'integer',
        'district_id' => 'integer',
        'village_id' => 'integer',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function creditApplications(): HasMany
    {
        return $this->hasMany(CreditApplication::class);
    }

    public function danaTunaiApplications(): HasMany
    {
        return $this->hasMany(DanaTunaiApplication::class);
    }

    public function cashLoanRequests(): HasMany
    {
        return $this->hasMany(CashLoanRequest::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }
}
