<?php

namespace App\Services;

use App\Models\Ad;
use Illuminate\Support\Str;

class AdsService
{
    public function listAll()
    {
        return Ad::query()->orderByDesc('updated_at')->get();
    }

    public function listActive()
    {
        return Ad::query()->where('active', true)->orderByDesc('updated_at')->get();
    }

    public function findByCode(string $code): ?Ad
    {
        return Ad::query()->where('code', $code)->first();
    }

    public function create(array $data): Ad
    {
        if (empty($data['code'])) {
            $data['code'] = (string) Str::uuid();
        }
        return Ad::query()->create($data);
    }

    public function update(Ad $ad, array $data): Ad
    {
        $ad->fill($data);
        $ad->save();
        return $ad;
    }

    public function delete(Ad $ad): void
    {
        $ad->delete();
    }

    public function parsePriceToCents(?string $price): int
    {
        if (!$price) {
            return 0;
        }
        $normalized = preg_replace('/[^0-9,\.]/', '', $price);
        if ($normalized === null || $normalized === '') {
            return 0;
        }
        $normalized = str_replace(['.', ','], ['', '.'], $normalized);
        $value = (float) $normalized;
        return (int) round($value * 100);
    }

    public function formatCents(int $cents): string
    {
        $value = number_format($cents / 100, 2, ',', '.');
        return 'R$ ' . $value;
    }
}
