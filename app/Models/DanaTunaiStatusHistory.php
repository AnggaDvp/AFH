<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanaTunaiStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'dana_tunai_application_id', 'from_status', 'to_status', 'changed_by', 'note', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(DanaTunaiApplication::class, 'dana_tunai_application_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
