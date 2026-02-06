<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentHookDelivery extends Model
{
    protected $fillable = [
        'hook_id',
        'event',
        'payload',
        'status_code',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function hook(): BelongsTo
    {
        return $this->belongsTo(PaymentHook::class, 'hook_id');
    }
}
