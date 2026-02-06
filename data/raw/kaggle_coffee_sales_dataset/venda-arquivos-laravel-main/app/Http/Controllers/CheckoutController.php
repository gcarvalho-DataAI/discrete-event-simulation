<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\AdsService;
use App\Services\CartService;
use App\Services\MercadoPagoService;
use App\Services\PaymentConfigService;
use App\Services\PaymentHookService;
use App\Services\ReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function show(AdsService $ads, CartService $cart)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->guest(route('account.login'));
        }

        $profileError = $this->ensureProfileComplete($user);
        if ($profileError) {
            return redirect()
                ->route('account.profile')
                ->withErrors(['profile' => $profileError]);
        }

        $items = $this->buildCartItems($ads, $cart);
        return view('checkout', [
            'user' => $user,
            'items' => $items,
            'total_cents' => $items->sum('line_total_cents'),
        ]);
    }

    public function submit(
        Request $request,
        AdsService $ads,
        CartService $cart,
        PaymentConfigService $paymentConfig,
        MercadoPagoService $mp,
        PaymentHookService $hooks,
        ReceiptService $receiptService
    ) {
        $user = Auth::user();
        if (!$user) {
            return redirect()->guest(route('account.login'));
        }

        $profileError = $this->ensureProfileComplete($user);
        if ($profileError) {
            return redirect()
                ->route('account.profile')
                ->withErrors(['profile' => $profileError]);
        }

        $items = $this->buildCartItems($ads, $cart);
        if ($items->isEmpty()) {
            return redirect()->route('cart.show');
        }

        $data = $request->validate([
            'payment_method' => 'nullable|string',
        ]);

        $totalCents = (int) $items->sum('line_total_cents');
        $isFreeOrder = $totalCents <= 0;

        $order = Order::query()->create([
            'status' => $isFreeOrder ? 'PAID' : 'PENDING',
            'total_cents' => $totalCents,
            'currency' => 'BRL',
            'customer_email' => $user->email,
            'customer_id' => $user->id,
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

        $hooks->dispatch('payment.created', [
            'orderId' => $order->id,
            'status' => $order->status,
            'totalCents' => $order->total_cents,
            'currency' => $order->currency,
            'provider' => $order->provider,
        ], $order->provider);

        if ($isFreeOrder) {
            $hooks->dispatch('payment.paid', [
                'orderId' => $order->id,
                'status' => $order->status,
                'totalCents' => $order->total_cents,
                'currency' => $order->currency,
                'provider' => $order->provider,
            ], $order->provider);

            $receiptService->sendReceipt($order->load('items'), $user->email);
            $cart->clear();

            return redirect()->route('checkout.success');
        }

        $config = $paymentConfig->get();
        $accessToken = $config['mp_access_token'];
        if (!$accessToken) {
            return redirect()->route('checkout.failure')->with('error', 'Pagamento indisponivel');
        }

        $apiBase = $config['mp_api_base_url'] ?: 'https://api.mercadopago.com';
        try {
            $webhookUrl = $this->resolveWebhookUrl($request);
        } catch (\RuntimeException $exception) {
            return redirect()->back()->withErrors([
                'checkout' => $exception->getMessage(),
            ]);
        }
        $paymentMethod = $this->normalizePaymentMethod($data['payment_method'] ?? $config['mp_payment_method']);

        $preferenceItems = $items->map(function ($item) {
            return [
                'id' => $item['ad']->code,
                'title' => $item['ad']->title,
                'quantity' => $item['quantity'],
                'unit_price' => $item['ad']->price_cents / 100,
                'currency_id' => 'BRL',
            ];
        })->values()->all();

        $nameParts = preg_split('/\s+/', trim((string) ($user->full_name ?: $user->name ?: 'Cliente'))) ?: ['Cliente'];
        $firstName = $nameParts[0] ?? 'Cliente';
        $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : ' ';

        if ($paymentMethod === 'pix') {
            $payment = $mp->createPixPayment([
                'amount' => $totalCents / 100,
                'description' => 'Pedido #' . $order->id,
                'payer' => [
                    'email' => $user->email,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'cpf' => preg_replace('/\D+/', '', (string) $user->cpf) ?: '00000000000',
                ],
                'notification_url' => $webhookUrl,
                'external_reference' => (string) $order->id,
            ], $accessToken, $apiBase);

            $order->update([
                'provider_payment_id' => (string) ($payment['id'] ?? null),
                'provider_status' => $payment['status'] ?? null,
                'provider_status_detail' => $payment['status_detail'] ?? null,
            ]);

            $qr = $payment['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null;
            $code = $payment['point_of_interaction']['transaction_data']['qr_code'] ?? null;

            return view('checkout-pix', [
                'order' => $order,
                'qr_base64' => $qr,
                'qr_code' => $code,
            ]);
        }

        $preference = $mp->createPreference([
            'items' => $preferenceItems,
            'payer_email' => $user->email,
            'external_reference' => (string) $order->id,
            'payment_method' => $paymentMethod,
            'back_urls' => [
                'success' => $this->resolveBaseUrl($request) . '/checkout/sucesso',
                'pending' => $this->resolveBaseUrl($request) . '/checkout/pendente',
                'failure' => $this->resolveBaseUrl($request) . '/checkout/falha',
            ],
            'notification_url' => $webhookUrl,
        ], $accessToken, $apiBase);

        $order->update([
            'provider_preference_id' => $preference['id'] ?? null,
        ]);

        $initPoint = $preference['init_point'] ?? $preference['sandbox_init_point'] ?? null;
        if ($initPoint) {
            return redirect()->away($initPoint);
        }

        return redirect()->route('checkout.failure');
    }

    public function success()
    {
        return view('checkout-success');
    }

    public function pending()
    {
        return view('checkout-pending');
    }

    public function failure()
    {
        return view('checkout-failure');
    }

    private function buildCartItems(AdsService $ads, CartService $cart)
    {
        $items = $cart->items();
        $adsByCode = $ads->listAll()->keyBy('code');

        return $items->map(function ($item) use ($adsByCode) {
            $ad = $adsByCode->get($item['id']);
            if (!$ad || !$ad->active) {
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

    private function ensureProfileComplete($user): ?string
    {
        if (!$user->full_name || !$user->cpf) {
            return 'Para finalizar o checkout, complete seu perfil com nome completo e CPF.';
        }

        return null;
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

        // empty, "both", "pix/card" and any unknown value fallback to both
        return null;
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
            throw new \RuntimeException(
                'Configure APP_URL com URL publica HTTPS (ngrok) ou defina MP_WEBHOOK_BASE_URL.'
            );
        }

        return $base . '/api/mercadopago/webhook';
    }
}
