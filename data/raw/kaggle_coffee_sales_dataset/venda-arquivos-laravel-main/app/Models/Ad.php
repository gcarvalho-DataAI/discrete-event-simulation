<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    protected $fillable = [
        'code',
        'title',
        'description',
        'price_text',
        'price_cents',
        'images',
        'cta_label',
        'cta_href',
        'active',
        'file_url',
    ];

    protected $casts = [
        'images' => 'array',
        'active' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
