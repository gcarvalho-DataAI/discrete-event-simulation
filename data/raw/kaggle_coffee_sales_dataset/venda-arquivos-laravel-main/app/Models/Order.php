<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'status',
        'total_cents',
        'currency',
        'customer_email',
        'customer_id',
        'provider',
        'provider_preference_id',
        'provider_payment_id',
        'provider_status',
        'provider_status_detail',
        'receipt_sent_at',
    ];

    protected $casts = [
        'receipt_sent_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
