<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MotorProduct extends Model
{
    protected $fillable = [
        'code',
        'name',
        'family',
        'status',
    ];

    public function brochureItems(): HasMany
    {
        return $this->hasMany(MotorBrochureItem::class);
    }

    public function creditApplications(): HasMany
    {
        return $this->hasMany(CreditApplication::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class);
    }
}
