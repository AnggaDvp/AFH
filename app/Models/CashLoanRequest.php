<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashLoanRequest extends Model
{
    protected $fillable = [
        'customer_id',
        'conversation_id',
        'submission_key',
        'nopol',
        'motor_brand',
        'stnk_name_type',
        'house_ownership',
        'status',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'conversation_id' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class);
    }

    public function chatContext(): array
    {
        return [
            'nopol' => $this->nopol,
            'motorBrand' => ucfirst($this->motor_brand),
            'stnkName' => [
                'self_name' => 'Atas Nama Sendiri',
                'different_name' => 'Beda Nama',
            ][$this->stnk_name_type] ?? $this->stnk_name_type,
            'houseOwnership' => [
                'owned' => 'Milik Sendiri',
                'family_owned' => 'Milik Keluarga',
                'rented' => 'Kontrak',
            ][$this->house_ownership] ?? $this->house_ownership,
            'status' => $this->status,
        ];
    }
}
