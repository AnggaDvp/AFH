<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'report_type',
        'filters',
        'file_name',
        'row_count',
        'created_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'row_count' => 'integer',
        'created_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
