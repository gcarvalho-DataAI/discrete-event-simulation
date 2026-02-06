<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    private const SESSION_KEY = 'cart.items';

    public function items(): Collection
    {
        $items = Session::get(self::SESSION_KEY, []);
        return collect($items);
    }

    public function add(string $adCode, int $quantity = 1): void
    {
        $items = $this->items();
        $current = (int) ($items->get($adCode)['quantity'] ?? 0);
        $items->put($adCode, ['id' => $adCode, 'quantity' => $current + $quantity]);
        Session::put(self::SESSION_KEY, $items->all());
    }

    public function set(string $adCode, int $quantity): void
    {
        $items = $this->items();
        if ($quantity <= 0) {
            $items->forget($adCode);
        } else {
            $items->put($adCode, ['id' => $adCode, 'quantity' => $quantity]);
        }
        Session::put(self::SESSION_KEY, $items->all());
    }

    public function remove(string $adCode): void
    {
        $items = $this->items();
        $items->forget($adCode);
        Session::put(self::SESSION_KEY, $items->all());
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function totalItems(): int
    {
        return (int) $this->items()->sum('quantity');
    }
}
