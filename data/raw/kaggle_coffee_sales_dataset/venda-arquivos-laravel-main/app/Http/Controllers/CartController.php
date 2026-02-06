<?php

namespace App\Http\Controllers;

use App\Services\AdsService;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(AdsService $ads, CartService $cart)
    {
        $items = $this->buildCartItems($ads, $cart);

        return view('cart', [
            'items' => $items,
            'total_cents' => $items->sum('line_total_cents'),
            'total_items' => $cart->totalItems(),
        ]);
    }

    public function add(Request $request, CartService $cart)
    {
        $adCode = (string) $request->input('id');
        $quantity = (int) $request->input('quantity', 1);
        if ($adCode) {
            $cart->add($adCode, max(1, $quantity));
        }

        return redirect()->route('cart.show');
    }

    public function update(Request $request, CartService $cart)
    {
        $adCode = (string) $request->input('id');
        $quantity = (int) $request->input('quantity', 1);
        if ($adCode) {
            $cart->set($adCode, $quantity);
        }

        return redirect()->route('cart.show');
    }

    public function remove(Request $request, CartService $cart)
    {
        $adCode = (string) $request->input('id');
        if ($adCode) {
            $cart->remove($adCode);
        }

        return redirect()->route('cart.show');
    }

    public function clear(CartService $cart)
    {
        $cart->clear();
        return redirect()->route('cart.show');
    }

    private function buildCartItems(AdsService $ads, CartService $cart)
    {
        $items = $cart->items();
        $adsByCode = $ads->listAll()->keyBy('code');

        return $items->map(function ($item) use ($adsByCode) {
            $ad = $adsByCode->get($item['id']);
            if (!$ad) {
                return null;
            }
            $quantity = (int) ($item['quantity'] ?? 1);
            $lineTotal = $ad->price_cents * $quantity;
            return [
                'ad' => $ad,
                'quantity' => $quantity,
                'line_total_cents' => $lineTotal,
            ];
        })->filter()->values();
    }
}
