<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MercadoPagoService;
use App\Services\PaymentConfigService;
use App\Services\PaymentHookService;
use App\Services\ReceiptService;
use Illuminate\Http\Request;

class MercadoPagoWebhookController extends Controller
{
    public function handle(
        Request $request,
        PaymentConfigService $config,
        MercadoPagoService $mp,
        PaymentHookService $hooks,
        ReceiptService $receipt
    ) {
        $settings = $config->get();
        $secret = $settings['mp_webhook_secret'] ?: env('MP_WEBHOOK_SECRET');

        if ($secret) {
            $signature = $request->header('x-signature');
            $requestId = $request->header('x-request-id');
            $dataId = $request->query('data.id') ?? $request->query('id');

            if (!$signature || !$requestId || !$dataId) {
                return response()->json(['error' => 'INVALID_SIGNATURE_HEADERS'], 401);
            }

            if (!$this->isSignatureValid($signature, $requestId, (string) $dataId, $secret)) {
                return response()->json(['error' => 'INVALID_SIGNATURE'], 401);
            }
        } elseif (app()->environment('production')) {
            return response()->json(['error' => 'MP_WEBHOOK_SECRET_MISSING'], 500);
        }

        $payload = $request->json()->all();
        $paymentId = $payload['data']['id'] ?? null;
        if (!$paymentId) {
            return response()->json(['received' => true]);
        }

        try {
            $accessToken = $settings['mp_access_token'];
            if (!$accessToken) {
                return response()->json(['error' => 'MP_ACCESS_TOKEN_MISSING'], 500);
            }
            $apiBase = $settings['mp_api_base_url'] ?: 'https://api.mercadopago.com';
            $payment = $mp->fetchPayment((string) $paymentId, $accessToken, $apiBase);

            $orderId = $payment['external_reference'] ?? null;
            if ($orderId) {
                $order = Order::query()->find($orderId);
                if ($order) {
                    $status = $this->mapPaymentStatus($payment['status'] ?? null);
                    $order->update([
                        'status' => $status,
                        'provider_payment_id' => (string) ($payment['id'] ?? null),
                        'provider_status' => $payment['status'] ?? null,
                        'provider_status_detail' => $payment['status_detail'] ?? null,
                    ]);

                    $eventPayload = [
                        'orderId' => $order->id,
                        'status' => $order->status,
                        'totalCents' => $order->total_cents,
                        'currency' => $order->currency,
                        'provider' => $order->provider,
                        'providerPaymentId' => $order->provider_payment_id,
                        'providerStatus' => $order->provider_status,
                    ];

                    $hooks->dispatch('payment.updated', $eventPayload, $order->provider);

                    if ($order->status === 'PAID') {
                        $hooks->dispatch('payment.paid', $eventPayload, $order->provider);
                        if ($order->customer_email) {
                            $receipt->sendReceipt($order->load('items'), $order->customer_email);
                        }
                    }

                    if ($order->status === 'CANCELED') {
                        $hooks->dispatch('payment.failed', $eventPayload, $order->provider);
                    }
                }
            }
        } catch (\Throwable $error) {
            if (!app()->environment('production')) {
                report($error);
            }
        }

        return response()->json(['received' => true]);
    }

    private function mapPaymentStatus(?string $status): string
    {
        if ($status === 'approved') {
            return 'PAID';
        }
        if ($status === 'rejected' || $status === 'cancelled') {
            return 'CANCELED';
        }
        return 'PENDING';
    }

    private function isSignatureValid(string $signature, string $requestId, string $dataId, string $secret): bool
    {
        [$ts, $v1] = $this->parseSignatureHeader($signature);
        if (!$ts || !$v1) {
            return false;
        }

        $manifest = 'id:' . $dataId . ';request-id:' . $requestId . ';ts:' . $ts . ';';
        $digest = hash_hmac('sha256', $manifest, $secret);
        return hash_equals($digest, $v1);
    }

    private function parseSignatureHeader(string $signature): array
    {
        $parts = array_map('trim', explode(',', $signature));
        $values = [];
        foreach ($parts as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);
            if ($key && $value) {
                $values[$key] = $value;
            }
        }
        return [$values['ts'] ?? null, $values['v1'] ?? null];
    }
}
