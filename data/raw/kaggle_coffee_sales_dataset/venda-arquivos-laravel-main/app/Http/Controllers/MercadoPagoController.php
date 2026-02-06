<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\AdsService;
use App\Services\MercadoPagoService;
use App\Services\PaymentConfigService;
use App\Services\PaymentHookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MercadoPagoController extends Controller
{
    public function publicKey(PaymentConfigService $config)
    {
        $settings = $config->get();
        return response()->json([
            'publicKey' => $settings['mp_public_key'],
        ]);
    }

    public function createPreference(
        Request $request,
        AdsService $ads,
        PaymentConfigService $config,
        MercadoPagoService $mp,
        PaymentHookService $hooks
    ) {
        $payload = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'email' => 'required|email',
        ]);

        $cartItems = $this->buildItemsFromPayload($ads, $payload['items']);
        if (empty($cartItems)) {
            return response()->json(['error' => 'ITEMS_INVALID'], 422);
        }

        $order = $this->createOrder($cartItems, $payload['email']);
        if ($order->provider === 'free') {
            $hooks->dispatch('payment.created', [
                'orderId' => $order->id,
                'status' => $order->status,
                'totalCents' => $order->total_cents,
                'currency' => $order->currency,
                'provider' => $order->provider,
            ], $order->provider);
            $hooks->dispatch('payment.paid', [
                'orderId' => $order->id,
                'status' => $order->status,
                'totalCents' => $order->total_cents,
                'currency' => $order->currency,
                'provider' => $order->provider,
            ], $order->provider);

            return response()->json([
                'id' => 'free-' . $order->id,
                'status' => 'approved',
                'external_reference' => (string) $order->id,
            ]);
        }

        $settings = $config->get();
        $accessToken = $settings['mp_access_token'];
        if (!$accessToken) {
            return response()->json(['error' => 'MP_ACCESS_TOKEN_MISSING'], 500);
        }

        $apiBase = $settings['mp_api_base_url'] ?: 'https://api.mercadopago.com';
        $baseUrl = $this->resolveBaseUrl($request);
        try {
            $webhookUrl = $this->resolveWebhookUrl($request);
        } catch (\RuntimeException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        $preference = $mp->createPreference([
            'items' => array_map(function ($item) {
                return [
                    'id' => $item['ad']->code,
                    'title' => $item['ad']->title,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['ad']->price_cents / 100,
                    'currency_id' => 'BRL',
                ];
            }, $cartItems),
            'payer_email' => $payload['email'],
            'external_reference' => (string) $order->id,
            'payment_method' => $this->normalizePaymentMethod($settings['mp_payment_method']),
            'back_urls' => [
                'success' => $baseUrl . '/checkout/sucesso',
                'pending' => $baseUrl . '/checkout/pendente',
                'failure' => $baseUrl . '/checkout/falha',
            ],
            'notification_url' => $webhookUrl,
        ], $accessToken, $apiBase);

        $order->update([
            'provider_preference_id' => $preference['id'] ?? null,
        ]);

        $hooks->dispatch('payment.created', [
            'orderId' => $order->id,
            'status' => $order->status,
            'totalCents' => $order->total_cents,
            'currency' => $order->currency,
            'provider' => $order->provider,
        ], $order->provider);

        return response()->json($preference);
    }

    public function createPix(
        Request $request,
        AdsService $ads,
        PaymentConfigService $config,
        MercadoPagoService $mp,
        PaymentHookService $hooks
    ) {
        $payload = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'email' => 'required|email',
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'cpf' => 'required|string',
        ]);

        $cartItems = $this->buildItemsFromPayload($ads, $payload['items']);
        if (empty($cartItems)) {
            return response()->json(['error' => 'ITEMS_INVALID'], 422);
        }

        $order = $this->createOrder($cartItems, $payload['email']);
        if ($order->provider === 'free') {
            $hooks->dispatch('payment.created', [
                'orderId' => $order->id,
                'status' => $order->status,
                'totalCents' => $order->total_cents,
                'currency' => $order->currency,
                'provider' => $order->provider,
            ], $order->provider);
            $hooks->dispatch('payment.paid', [
                'orderId' => $order->id,
                'status' => $order->status,
                'totalCents' => $order->total_cents,
                'currency' => $order->currency,
                'provider' => $order->provider,
            ], $order->provider);

            return response()->json([
                'id' => 'free-' . $order->id,
                'status' => 'approved',
                'external_reference' => (string) $order->id,
            ]);
        }

        $settings = $config->get();
        $accessToken = $settings['mp_access_token'];
        if (!$accessToken) {
            return response()->json(['error' => 'MP_ACCESS_TOKEN_MISSING'], 500);
        }

        $apiBase = $settings['mp_api_base_url'] ?: 'https://api.mercadopago.com';
        try {
            $webhookUrl = $this->resolveWebhookUrl($request);
        } catch (\RuntimeException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        $payment = $mp->createPixPayment([
            'amount' => $order->total_cents / 100,
            'description' => 'Pedido #' . $order->id,
            'payer' => [
                'email' => $payload['email'],
                'first_name' => $payload['firstName'],
                'last_name' => $payload['lastName'],
                'cpf' => $payload['cpf'],
            ],
            'notification_url' => $webhookUrl,
            'external_reference' => (string) $order->id,
        ], $accessToken, $apiBase);

        $order->update([
            'provider_payment_id' => (string) ($payment['id'] ?? null),
            'provider_status' => $payment['status'] ?? null,
            'provider_status_detail' => $payment['status_detail'] ?? null,
        ]);

        $hooks->dispatch('payment.created', [
            'orderId' => $order->id,
            'status' => $order->status,
            'totalCents' => $order->total_cents,
            'currency' => $order->currency,
            'provider' => $order->provider,
        ], $order->provider);

        return response()->json($payment);
    }

    private function buildItemsFromPayload(AdsService $ads, array $items): array
    {
        $adMap = $ads->listAll()->keyBy('code');
        $payload = [];
        foreach ($items as $item) {
            $ad = $adMap->get($item['id']);
            if (!$ad || !$ad->active) {
                continue;
            }
            $payload[] = [
                'ad' => $ad,
                'quantity' => (int) $item['quantity'],
            ];
        }
        return $payload;
    }

    private function createOrder(array $items, string $email): Order
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['ad']->price_cents * $item['quantity'];
        }

        $isFreeOrder = $total <= 0;

        $order = Order::query()->create([
            'status' => $isFreeOrder ? 'PAID' : 'PENDING',
            'total_cents' => $total,
            'currency' => 'BRL',
            'customer_email' => $email,
            'customer_id' => Auth::id(),
            'provider' => $isFreeOrder ? 'free' : 'mercadopago',
        ]);

        foreach ($items as $item) {
            OrderItem::query()->create([
                'order_id' => $order->id,
                'ad_id' => $item['ad']->id,
                'name' => $item['ad']->title,
                'quantity' => $item['quantity'],
                'unit_price_cents' => $item['ad']->price_cents,
            ]);
        }

        return $order;
    }

    private function resolveBaseUrl(Request $request): string
    {
        $configured = rtrim((string) config('app.url'), '/');
        if (filter_var($configured, FILTER_VALIDATE_URL)) {
            return $configured;
        }

        return rtrim($request->getSchemeAndHttpHost(), '/');
    }

    private function resolveWebhookUrl(Request $request): string
    {
        $publicBase = rtrim((string) env('MP_WEBHOOK_BASE_URL', ''), '/');
        $base = $publicBase !== '' ? $publicBase : $this->resolveBaseUrl($request);

        if (
            str_contains($base, 'localhost')
            || str_contains($base, '127.0.0.1')
            || str_starts_with($base, 'http://')
        ) {
            throw new \RuntimeException('Configure APP_URL HTTPS publico ou MP_WEBHOOK_BASE_URL.');
        }

        return $base . '/api/mercadopago/webhook';
    }

    private function normalizePaymentMethod(?string $value): ?string
    {
        $raw = strtolower(trim((string) $value));
        if ($raw === 'pix') {
            return 'pix';
        }
        if ($raw === 'card') {
            return 'card';
        }

        return null;
    }
}
