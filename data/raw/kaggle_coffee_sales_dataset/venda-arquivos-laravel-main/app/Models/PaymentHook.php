<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentHook extends Model
{
    protected $fillable = [
        'name',
        'url',
        'provider',
        'event',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(PaymentHookDelivery::class, 'hook_id');
    }
}
